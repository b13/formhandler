<?php

namespace Typoheads\Formhandler\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Component\AbstractClass;
use Typoheads\Formhandler\Component\AbstractComponent;
use Typoheads\Formhandler\Component\ComponentProcessResult;
use Typoheads\Formhandler\Component\Configuration;
use Typoheads\Formhandler\Interceptor\RemoveXSS;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Default controller for Formhandler
 */
class FormController extends AbstractClass
{
    protected array $gp = []; // The current GET/POST parameters of the form
    protected array $errors = []; // Contains all errors occurred while validation
    protected string $formValuesPrefix = ''; // Holds the prefix value of all parameters of this form.
    protected bool $submitted = false; // flag indicating if the form got submitted

    protected ?\Typoheads\Formhandler\View\FormView $view = null;

    protected int $currentStep = 2;
    protected int $lastStep = 1;
    protected int $totalSteps = 0;
    protected bool $finished = false;

    protected string $predefined = '';
    protected string $templateFile = '';
    protected array $langFiles = [];
    protected ?Configuration $configuration = null;
    protected ResponseFactory $responseFactory;
    protected StreamFactory $streamFactory;

    public function __construct()
    {
        parent::__construct();
        $this->responseFactory = GeneralUtility::makeInstance(ResponseFactory::class);
        $this->streamFactory = GeneralUtility::makeInstance(StreamFactory::class);

    }

    public function process(): ResponseInterface
    {
        $this->init();
        $this->storeFileNamesInGP();
        $this->processFileRemoval();

        if (!$this->submitted) {
            return $this->processNotSubmitted();
        }
        return $this->processSubmitted();
    }

    /**
     * Process the form if the user clicked submit.
     *
     * @return string The generated content
     */
    protected function processSubmitted(): ResponseInterface
    {

        //run init interceptors
        $this->addFormhandlerClass($this->settings['initInterceptors.'], RemoveXSS::class);
        $output = $this->runClasses($this->settings['initInterceptors.'] ?? []);
        if ($output->hasResponse()) {
            return $output->response;
        }

        //Search for completely unchecked checkbox arrays before validation to make sure that no values from session are taken.
        if ($this->currentStep > $this->lastStep) {
            $currentGP = $this->utilityFuncs->getMergedGP();
            if (isset($this->settings['checkBoxFields'])) {
                $checkBoxFields = $this->utilityFuncs->getSingle($this->settings, 'checkBoxFields');
                $fields = GeneralUtility::trimExplode(',', $checkBoxFields);
                foreach ($fields as $idx => $field) {
                    if (isset($this->gp[$field]) && !isset($currentGP[$field])) {
                        unset($this->gp[$field]);
                    }
                }
            }
            $this->globals->setGP($this->gp);
        }

        $this->globals->setRandomID($this->gp['randomID'] ?? null);

        //run validation
        $this->errors = [];
        $valid = [true];
        if ($this->currentStep >= $this->lastStep) {
            $this->validateErrorCheckConfig();
        }
        if (isset($this->settings['validators.']) &&
            is_array($this->settings['validators.']) &&
            (int)($this->utilityFuncs->getSingle($this->settings['validators.'], 'disable')) !== 1
        ) {
            foreach ($this->settings['validators.'] ?? [] as $idx => $tsConfig) {
                if ($idx !== 'disable') {
                    $className = $this->utilityFuncs->getPreparedClassName($tsConfig);
                    if (is_array($tsConfig) && strlen($className) > 0) {
                        if ((int)($this->utilityFuncs->getSingle($tsConfig, 'disable')) !== 1) {
                            $validator = $this->componentManager->getComponent($className);
                            if ($this->currentStep === $this->lastStep) {
                                $userSetting = GeneralUtility::trimExplode(',', (string)$this->utilityFuncs->getSingle($tsConfig['config.'], 'restrictErrorChecks'));
                                $autoSetting = [
                                    'fileAllowedTypes',
                                    'fileRequired',
                                    'fileMaxCount',
                                    'fileMinCount',
                                    'fileMaxSize',
                                    'fileMinSize',
                                    'fileMaxTotalSize',
                                ];
                                $merged = array_merge($userSetting, $autoSetting);
                                $tsConfig['config.']['restrictErrorChecks'] = implode(',', $merged);
                                unset($tsConfig['config.']['restrictErrorChecks.']);
                            }
                            $tsConfig['config.'] = $this->addDefaultComponentConfig($tsConfig['config.']);
                            $validator->init($this->gp, $tsConfig['config.']);
                            $validator->validateConfig();
                            $res = $validator->validate($this->errors);
                            array_push($valid, $res);
                        }
                    } else {
                        $this->utilityFuncs->throwException('classesarray_error');
                    }
                }
            }
        }

        //process files
        if ($this->currentStep >= $this->lastStep) {
            $this->processFiles();
        }

        //if form is valid
        if ($this->isValid($valid)) {

            //read template file
            $this->templateFile = $this->utilityFuncs->readTemplateFile($this->templateFile, $this->settings);
            $this->globals->setTemplateCode($this->templateFile);
            $this->langFiles = $this->utilityFuncs->readLanguageFiles($this->langFiles, $this->settings);
            $this->globals->setLangFiles($this->langFiles);

            $this->view->setLangFiles($this->langFiles);
            $this->view->setSettings($this->settings);
            $this->setViewSubpart($this->currentStep);

            $this->storeGPinSession();
            $this->mergeGPWithSession();


            //if no more steps
            if ($this->finished) {
                return $this->processFinished();
            }
            $content = $this->view->render($this->gp, $this->errors);
            return $this->responseFactory->createResponse()
                ->withBody($this->streamFactory->createStream($content));
        }
        $this->templateFile = $this->utilityFuncs->readTemplateFile($this->templateFile, $this->settings);
        $this->globals->setTemplateCode($this->templateFile);
        $this->langFiles = $this->utilityFuncs->readLanguageFiles($this->langFiles, $this->settings);
        $this->globals->setLangFiles($this->langFiles);

        $this->view->setLangFiles($this->langFiles);
        $this->view->setSettings($this->settings);
        $this->setViewSubpart($this->currentStep);
        return $this->processNotValid();
    }

    /**
     * Validate if the error checks have all been set correctly.
     */
    protected function validateErrorCheckConfig()
    {
        if (!empty($_FILES)) {

            //for all file properties
            foreach ($_FILES as $sthg => $files) {

                //if a file upload field exists
                if (isset($files['name']) && is_array($files['name'])) {

                    //for all file names
                    $uploadFields = array_keys($files['name']);
                    foreach ($uploadFields as $field) {

                        //if a file was uploaded through this field
                        if (!is_array($files['tmp_name'][$field])) {
                            $files['tmp_name'][$field] = [$files['tmp_name'][$field]];
                        }
                        if (count($files['tmp_name'][$field]) > 0) {
                            $hasAllowedTypesCheck = false;
                            if (isset($this->settings['validators.']) &&
                                is_array($this->settings['validators.']) &&
                                (int)($this->utilityFuncs->getSingle($this->settings['validators.'], 'disable')) !== 1
                            ) {
                                foreach ($this->settings['validators.'] as $idx => $tsConfig) {
                                    if (isset($tsConfig['config.']) && isset($tsConfig['config.']['fieldConf.']) && isset($tsConfig['config.']['fieldConf.'][$field . '.']) && isset($tsConfig['config.']['fieldConf.'][$field . '.']['errorCheck.'])) {
                                        foreach ($tsConfig['config.']['fieldConf.'][$field . '.']['errorCheck.'] as $errorCheck) {
                                            if ($errorCheck === 'fileAllowedTypes') {
                                                $hasAllowedTypesCheck = true;
                                            }
                                        }
                                    }
                                }
                            }
                            if (!$hasAllowedTypesCheck) {
                                $missingChecks = [];
                                if (!$hasAllowedTypesCheck) {
                                    $missingChecks[] = 'fileAllowedTypes';
                                }
                                $this->utilityFuncs->throwException('error_checks_missing', implode(',', $missingChecks), $field);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Process a form containing errors.
     */
    protected function processNotValid(): ResponseInterface
    {
        $this->gp['formErrors'] = $this->errors;
        $this->globals->setGP($this->gp);

        //stay on current step
        if ($this->lastStep < $this->globals->getSession()->get('currentStep')) {
            $this->globals->getSession()->set('currentStep', $this->lastStep);
            $this->currentStep = $this->lastStep;
        }

        $this->globals->getSession()->set('settings', $this->settings);

        //read template file
        $this->templateFile = $this->utilityFuncs->readTemplateFile($this->templateFile, $this->settings);
        $this->globals->setTemplateCode($this->templateFile);
        $this->langFiles = $this->utilityFuncs->readLanguageFiles($this->langFiles, $this->settings);
        $this->globals->setLangFiles($this->langFiles);

        $this->view->setLangFiles($this->langFiles);
        $this->view->setSettings($this->settings);

        //reset the template because step had probably been decreased
        $this->setViewSubpart($this->currentStep);

        if ($this->currentStep >= $this->lastStep) {
            $this->storeGPinSession();
            $this->mergeGPWithSession();
        }

        $content = $this->view->render($this->gp, $this->errors);
        return $this->responseFactory->createResponse()
            ->withBody($this->streamFactory->createStream($content));
    }

    /**
     * Process a form containing no more steps (a form which is finished)
     */
    protected function processFinished(): ResponseInterface
    {

        //If skipView is set, call preProcessors and initInterceptors here
        if ((int)($this->utilityFuncs->getSingle($this->settings, 'skipView')) === 1) {

            //run preProcessors
            $output = $this->runClasses($this->settings['preProcessors.'] ?? []);
            if ($output->hasResponse()) {
                return $output->response;
            }

            //run init interceptors
            $this->addFormhandlerClass($this->settings['initInterceptors.'], 'Interceptor\\RemoveXSS');
            $output = $this->runClasses($this->settings['initInterceptors.'] ?? []);
            if ($output->hasResponse()) {
                return $output->response;
            }
        }
        $this->storeSettingsInSession();

        //run save interceptors
        $this->addFormhandlerClass($this->settings['saveInterceptors.'], 'Interceptor\\RemoveXSS');
        $output = $this->runClasses($this->settings['saveInterceptors.'] ?? []);
        if ($output->hasResponse()) {
            return $output->response;
        }

        //run loggers
        $this->addFormhandlerClass($this->settings['loggers.'], 'Logger_DB');
        $output = $this->runClasses($this->settings['loggers.'] ?? []);
        if ($output->hasResponse()) {
            return $output->response;
        }

        //run finishers
        if (isset($this->settings['finishers.']) && is_array($this->settings['finishers.']) && (int)($this->utilityFuncs->getSingle($this->settings['finishers.'], 'disable')) !== 1) {
            ksort($this->settings['finishers.']);

            foreach ($this->settings['finishers.'] as $idx => $tsConfig) {
                if ($idx !== 'disabled') {
                    $className = $this->utilityFuncs->getPreparedClassName($tsConfig);
                    if (is_array($tsConfig) && strlen($className) > 0) {
                        if ((int)($this->utilityFuncs->getSingle($tsConfig, 'disable')) !== 1) {
                            /** @var AbstractComponent $finisher */
                            $finisher = $this->componentManager->getComponent($className);
                            $tsConfig['config.'] = $this->addDefaultComponentConfig($tsConfig['config.']);
                            $finisher->init($this->gp, $tsConfig['config.']);
                            $finisher->validateConfig();
                            $result = $finisher->process();
                            if ($result->hasResponse()) {
                                $this->globals->getSession()->set('finished', true);
                                return $result->response;
                            }
                            if ($result->hasGp()) {
                                $this->gp = $result->gp;
                                $this->globals->setGP($this->gp);
                            }
                        }
                    } else {
                        $this->utilityFuncs->throwException('classesarray_error');
                    }
                }
            }
            $this->globals->getSession()->set('finished', true);
        }
        $response = $this->responseFactory->createResponse()
            ->withBody($this->streamFactory->createStream('finished'));
        return $response;
    }

    /**
     * Process a form which has not been submitted.
     */
    protected function processNotSubmitted(): ResponseInterface
    {

        $this->view->setSettings($this->settings);

        $this->templateFile = $this->utilityFuncs->readTemplateFile($this->templateFile, $this->settings);
        $this->globals->setTemplateCode($this->templateFile);
        $this->langFiles = $this->utilityFuncs->readLanguageFiles($this->langFiles, $this->settings);
        $this->globals->setLangFiles($this->langFiles);

        $this->view->setLangFiles($this->langFiles);
        $this->setViewSubpart($this->currentStep);

        $output = $this->runClasses($this->settings['preProcessors.'] ?? []);
        if ($output->hasResponse()) {
            return $output->response;
        }

        $this->addFormhandlerClass($this->settings['initInterceptors.'], 'Interceptor\\RemoveXSS');
        $output = $this->runClasses($this->settings['initInterceptors.'] ?? []);
        if ($output->hasResponse()) {
            return $output->response;
        }

        $content = $this->view->render($this->gp, $this->errors);
        return $this->responseFactory->createResponse()
            ->withBody($this->streamFactory->createStream($content));
    }

    /**
     * Stores file names of uploaded files into the internal GET/POST parameters storage ($this->gp) so that they can be used later on in "value markers", userFuncs, ...
     */
    protected function storeFileNamesInGP()
    {

        //put file names into $this->gp
        $sessionFiles = $this->globals->getSession()->get('files');
        if (!is_array($sessionFiles)) {
            $sessionFiles = [];
        }
        foreach ($sessionFiles as $fieldname => $files) {
            $fileNames = [];
            if (is_array($files)) {
                foreach ($files as $idx => $fileInfo) {
                    $fileName = $fileInfo['uploaded_name'];
                    if (!$fileName) {
                        $fileName = $fileInfo['name'];
                    }
                    $fileNames[] = $fileName;
                }
            }
            $this->gp[$fieldname] = implode(',', $fileNames);
        }
    }

    /**
     * Adds default configuration for every Formhandler component to the given configuration array
     *
     * @param array $conf The configuration of the component set in TS
     * @return array The initial configuration plus the default configuration
     */
    protected function addDefaultComponentConfig($conf)
    {
        if (!isset($conf['langFiles'])) {
            $conf['langFiles'] = $this->langFiles;
        }
        $conf['formValuesPrefix'] = $this->settings['formValuesPrefix'] ?? null;
        $conf['templateSuffix'] = $this->settings['templateSuffix'] ?? null;
        return $conf;
    }

    /**
     * Adds a mandatory component to the classes array
     */
    protected function addFormhandlerClass(&$classesArray, $className)
    {
        if (!isset($classesArray) && !is_array($classesArray)) {

            //add class to the end of the array
            $classesArray[] = ['class' => $className];
        } else {
            $found = false;
            $className = $this->utilityFuncs->prepareClassName($className);
            foreach ($classesArray as $idx => $classOptions) {
                $currentClassName = $this->utilityFuncs->getPreparedClassName($classOptions);
                if ($className === $currentClassName) {
                    $found = true;
                }
            }
            if (!$found) {

                //add class to the end of the array
                $classesArray[] = ['class' => $className];
            }
        }
    }

    /**
     * Removes files from the internal file storage
     */
    protected function processFileRemoval()
    {
        if (isset($this->gp['removeFile'])) {
            $filename = $this->gp['removeFile'];
            $fieldname = $this->gp['removeFileField'];
            $sessionFiles = $this->globals->getSession()->get('files');
            if (is_array($sessionFiles)) {
                foreach ($sessionFiles as $field => $files) {
                    if (!strcmp($field, $fieldname)) {

                        //get upload folder
                        $uploadFolder = $this->utilityFuncs->getTempUploadFolder($field);

                        //build absolute path to upload folder
                        $uploadPath = $this->utilityFuncs->getTYPO3Root() . $uploadFolder;
                        $found = false;
                        foreach ($files as $key => $fileInfo) {
                            if (!strcmp($fileInfo['uploaded_name'], $filename)) {
                                $found = true;
                                unset($sessionFiles[$field][$key]);
                                if (file_exists($uploadPath . $fileInfo['uploaded_name'])) {
                                    unlink($uploadPath . $fileInfo['uploaded_name']);
                                }
                            }
                        }
                        if (!$found) {
                            foreach ($files as $key => $fileInfo) {
                                if (!strcmp($fileInfo['name'], $filename)) {
                                    unset($sessionFiles[$field][$key]);
                                    if (file_exists($uploadPath . $fileInfo['name'])) {
                                        unlink($uploadPath . $fileInfo['name']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            unset($this->gp['removeFile']);
            unset($this->gp['removeFileField']);
            $this->globals->getSession()->set('files', $sessionFiles);
        }
    }

    /**
     * Processes uploaded files, moves them to a temporary upload folder, renames them if they already exist and
     * stores the information in user session
     */
    protected function processFiles()
    {
        $sessionFiles = $this->globals->getSession()->get('files');
        $tempFiles = $sessionFiles;

        if (!empty($_FILES)) {
            $uploadedFilesWithSameNameAction = $this->utilityFuncs->getSingle($this->settings['files.'], 'uploadedFilesWithSameName');
            if (!$uploadedFilesWithSameNameAction) {
                $uploadedFilesWithSameNameAction = 'ignore';
            }

            //for all file properties
            foreach ($_FILES as $sthg => $files) {

                //if a file was uploaded
                if (isset($files['name']) && is_array($files['name'])) {

                    //for all file names
                    foreach ($files['name'] as $field => $uploadedFiles) {

                        //If only a single file is uploaded
                        if (!is_array($uploadedFiles)) {
                            $uploadedFiles = [$uploadedFiles];
                        }

                        if (!isset($this->errors[$field])) {

                            //get upload folder
                            $uploadFolder = $this->utilityFuncs->getTempUploadFolder($field);

                            //build absolute path to upload folder
                            $uploadPath = $this->utilityFuncs->getTYPO3Root() . $uploadFolder;

                            if (!file_exists($uploadPath)) {
                                return;
                            }

                            foreach ($uploadedFiles as $idx => $name) {
                                $exists = false;
                                if (is_array($sessionFiles[$field] ?? null)) {
                                    foreach ($sessionFiles[$field] as $fileId => $fileOptions) {
                                        if ($fileOptions['name'] === $name) {
                                            $exists = true;
                                        }
                                    }
                                }
                                if (!$exists || $uploadedFilesWithSameNameAction === 'replace' || $uploadedFilesWithSameNameAction === 'append') {
                                    $name = $this->utilityFuncs->doFileNameReplace($name);
                                    $filename = substr($name, 0, strpos($name, '.'));
                                    if (strlen($filename) > 0) {
                                        $ext = substr($name, strpos($name, '.'));
                                        $suffix = 1;

                                        //build file name
                                        $uploadedFileName = $filename . $ext;

                                        if ($uploadedFilesWithSameNameAction !== 'replace') {

                                            //rename if exists
                                            while (file_exists($uploadPath . $uploadedFileName)) {
                                                $uploadedFileName = $filename . '_' . $suffix . $ext;
                                                $suffix++;
                                            }
                                        }
                                        $files['name'][$field][$idx] = $uploadedFileName;

                                        //move from temp folder to temp upload folder
                                        if (!is_array($files['tmp_name'][$field])) {
                                            $files['tmp_name'][$field] = [$files['tmp_name'][$field]];
                                        }
                                        move_uploaded_file($files['tmp_name'][$field][$idx], $uploadPath . $uploadedFileName);
                                        GeneralUtility::fixPermissions($uploadPath . $uploadedFileName);
                                        $files['uploaded_name'][$field][$idx] = $uploadedFileName;

                                        //set values for session
                                        $tmp['name'] = $name;
                                        $tmp['uploaded_name'] = $uploadedFileName;
                                        $tmp['uploaded_path'] = $uploadPath;
                                        $tmp['uploaded_folder'] = $uploadFolder;

                                        $uploadedUrl = rtrim(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), '/');
                                        $uploadedUrl .= '/' . trim($uploadFolder, '/') . '/';
                                        $uploadedUrl .= trim($uploadedFileName, '/');

                                        $tmp['uploaded_url'] = $uploadedUrl;
                                        $tmp['size'] = $files['size'][$field][$idx];
                                        if (is_array($files['type'][$field][$idx])) {
                                            $tmp['type'] = $files['type'][$field][$idx];
                                        } else {
                                            $tmp['type'] = $files['type'][$field];
                                        }
                                        if (!is_array($tempFiles[$field] ?? null) && strlen((string)$field) > 0) {
                                            $tempFiles[$field] = [];
                                        }
                                        if (!$exists || $uploadedFilesWithSameNameAction !== 'replace') {
                                            array_push($tempFiles[$field], $tmp);
                                        }
                                        if (!is_array($this->gp[$field] ?? null)) {
                                            $this->gp[$field] = [];
                                        }
                                        if (!$exists || $uploadedFilesWithSameNameAction !== 'replace') {
                                            array_push($this->gp[$field], $uploadedFileName);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->globals->getSession()->set('files', $tempFiles);
    }

    /**
     * Stores the current GET/POST parameters in SESSION
     *
     * @param array &$settings Reference to the settings array to get information about checkboxes and radiobuttons.
     */
    protected function storeGPinSession()
    {
        $newGP = $this->utilityFuncs->getMergedGP();
        $data = $this->globals->getSession()->get('values');

        $checkBoxFields = $this->utilityFuncs->getSingle($this->settings, 'checkBoxFields');
        $checkBoxFields = GeneralUtility::trimExplode(',', (string)$checkBoxFields);

        //set the variables in session
        if ($this->lastStep !== $this->currentStep) {
            foreach ($newGP as $key => $value) {
                if (!strstr($key, 'step-') && $key !== 'submitted' && $key !== 'randomID' &&
                    $key !== 'removeFile' && $key !== 'removeFileField' && $key !== 'submitField'
                ) {
                    $data[$this->lastStep][$key] = $newGP[$key];
                }
            }
        }

        //Search for checkboxes which were unchecked in this step.
        foreach ($checkBoxFields as $field) {
            if (!isset($newGP[$field])) {
                unset($data[$this->lastStep][$field]);
            }
        }
        $this->globals->getSession()->set('values', $data);
    }

    /**
     * Resets the values in session to have a clean form
     */
    protected function reset($gp = [])
    {
        $values = [
            'creationTstamp' => time(),
            'values' => null,
            'files' => null,
            'lastStep' => null,
            'currentStep' => 1,
            'startblock' => null,
            'endblock' => null,
            'inserted_uid' => null,
            'inserted_tstamp' => null,
            'key_hash' => null,
            'finished' => null,
        ];
        $this->globals->getSession()->setMultiple($values);
        $this->gp = $gp;
        $this->currentStep = 1;
        $this->globals->setGP($this->gp);
    }

    /**
     * Validates the Formhandler config.
     * E.g. If email addresses were set in flexform then Finisher_Mail must exist in the TS configuration.
     */
    public function validateConfig(): void
    {
        $options = [
            ['to_email', 'sEMAILADMIN', 'finishers', $this->utilityFuncs->prepareClassName('\Typoheads\Formhandler\Finisher\Mail')],
            ['to_email', 'sEMAILUSER', 'finishers', $this->utilityFuncs->prepareClassName('\Typoheads\Formhandler\Finisher\Mail')],
            ['redirect_page', 'sMISC', 'finishers', $this->utilityFuncs->prepareClassName('\Typoheads\Formhandler\Finisher\Redirect')],
            ['required_fields', 'sMISC', 'validators', $this->utilityFuncs->prepareClassName('\Typoheads\Formhandler\Validator\DefaultValidator')],
        ];
        foreach ($options as $idx => $option) {
            $fieldName = $option[0];
            $flexformSection = $option[1];
            $component = $option[2];
            $componentName = $option[3];
            $value = $this->utilityFuncs->pi_getFFvalue($this->cObj->data['pi_flexform'], $fieldName, $flexformSection);

            // Check if a Mail Finisher can be found in the config
            $isConfigOk = false;
            if (is_array($this->settings[$component . '.'])) {
                foreach ($this->settings[$component . '.'] as $finisher) {
                    $className = $this->utilityFuncs->getPreparedClassName($finisher);
                    if ($className == $componentName || @is_subclass_of($className, $componentName)) {
                        $isConfigOk = true;
                        break;
                    }
                }
            }

            if ($value != '' && !$isConfigOk) {
                $this->utilityFuncs->throwException('missing_component', $component, $value, $componentName);
            }
        }
    }

    /**
     * Init method for the controller.
     * This method sets internal values, initializes the ajax handler and the session.
     */
    protected function init()
    {
        $this->configuration = GeneralUtility::makeInstance(Configuration::class);
        $this->settings = $this->getSettings();
        $this->formValuesPrefix = $this->utilityFuncs->getSingle($this->settings, 'formValuesPrefix');
        $this->globals->setFormID($this->utilityFuncs->getSingle($this->settings, 'formID'));
        $this->globals->setFormValuesPrefix($this->formValuesPrefix);
        $this->gp = $this->utilityFuncs->getMergedGP();

        if (!isset($this->settings['uniqueFormID']) || !$this->settings['uniqueFormID']) {
            if (isset($this->gp['randomID'])) {
                $this->gp['randomID'] = preg_replace('/[^0-9a-z]/', '', preg_quote($this->gp['randomID']));
            } else {
                $this->gp['randomID'] = null;
            }
        }
        $randomID = $this->gp['randomID'];
        if (!$randomID) {
            if ($this->settings['uniqueFormID'] ?? false) {
                $randomID = $this->utilityFuncs->getSingle($this->settings, 'uniqueFormID');
            } else {
                $randomID = $this->utilityFuncs->generateRandomID();
            }
        }
        $this->globals->setRandomID($randomID);

        $sessionClass = $this->utilityFuncs->getPreparedClassName($this->settings['session.'] ?? null, 'Session\PHP');
        $session = $this->componentManager->getComponent($sessionClass);
        $sessionConfig = [];
        if (isset($this->settings['session.']) && isset($this->settings['session.']['config.'])) {
            $sessionConfig = $this->settings['session.']['config.'];
        }
        $session->init($this->gp, $sessionConfig);
        $session->start();
        $this->globals->setSession($session);

        $action = $this->request->getParsedBody()['action'] ?? $this->request->getQueryParams()['action'] ?? null;
        if ($this->globals->getFormValuesPrefix()) {
            $temp = $this->request->getParsedBody()[$this->globals->getFormValuesPrefix()] ?? $this->request->getQueryParams()[$this->globals->getFormValuesPrefix()] ?? null;
            $action = $temp['action'] ?? null;
        }
        if ($this->globals->getSession()->get('finished') && !$action) {
            $this->globals->getSession()->reset();
            unset($_GET[$this->globals->getFormValuesPrefix()]);
            unset($_GET['id']);
            $this->utilityFuncs->doRedirect($GLOBALS['TSFE']->id, false, $_GET);
            exit();
        }

        $currentStepFromSession = $this->globals->getSession()->get('currentStep');
        $prevStep = $currentStepFromSession;
        if ((int)$prevStep !== (int)$currentStepFromSession) {
            $this->currentStep = 1;
            $this->lastStep = 1;
            $this->utilityFuncs->throwException('You messed with the steps!');
        }

        $this->mergeGPWithSession();

        if ((int)($this->utilityFuncs->getSingle($this->settings, 'disableConfigValidation')) === 0) {
            $this->validateConfig();
        }
        $this->globals->setSettings($this->settings);

        $this->globals->getSession()->set('predef', $this->globals->getPredef());

        $this->storeSettingsInSession();

        $this->mergeGPWithSession();

        $this->submitted = $this->isFormSubmitted();

        $this->globals->setSubmitted($this->submitted);
        if ($this->globals->getSession()->get('creationTstamp') === null) {
            if ($this->submitted) {
                $this->reset($this->gp);
            } else {
                $this->reset();
            }
        }


        $this->view = $this->componentManager->getComponent(\Typoheads\Formhandler\View\FormView::class);
        $this->view->setLangFiles($this->langFiles);
        $this->view->setSettings($this->settings);

        $this->globals->setGP($this->gp);

        if (!isset($this->gp['randomID'])) {
            $this->gp['randomID'] = $this->globals->getRandomID();
        }
    }

    /**
     * Checks if the form has been submitted
     */
    protected function isFormSubmitted(): bool
    {
        $submitted = $this->gp['submitted'] ?? false;
        if ($submitted) {
            foreach ($this->gp as $key => $value) {
                if (substr($key, 0, 5) === 'step-') {
                    $submitted = true;
                }
            }
        } elseif ((int)($this->utilityFuncs->getSingle($this->settings, 'skipView')) === 1) {
            $submitted = true;
        }

        return $submitted;
    }

    /**
     * Sets the template of the view.
     *
     * @param int The current step
     */
    protected function setViewSubpart($step)
    {
        $this->finished = false;

        if ((int)($this->utilityFuncs->getSingle($this->settings, 'skipView')) === 1) {
            $this->finished = true;
        } elseif (isset($this->settings['templateSuffix']) && strstr($this->templateFile, ('###TEMPLATE_FORM' . $step . $this->settings['templateSuffix'] . '###'))) {

            // search for ###TEMPLATE_FORM[step][suffix]###
            $this->view->setTemplate($this->templateFile, ('FORM' . $step . $this->settings['templateSuffix']));
        } elseif (!isset($this->settings['templateSuffix']) && strstr($this->templateFile, ('###TEMPLATE_FORM' . $step . '###'))) {

            //search for ###TEMPLATE_FORM[step]###
            $this->view->setTemplate($this->templateFile, ('FORM' . $step));
        } elseif ((int)$step === (int)($this->globals->getSession()->get('lastStep')) + 1) {
            $this->finished = true;
        }
    }

    /**
     * Stores some settings of the form into the session
     */
    protected function storeSettingsInSession()
    {
        $values = [
            'formValuesPrefix' => $this->formValuesPrefix,
            'settings' => $this->settings,
            'currentStep' => $this->currentStep,
            'totalSteps' => $this->totalSteps,
            'lastStep' => $this->lastStep,
            'templateSuffix' => $this->settings['templateSuffix'] ?? null,
        ];
        $this->globals->getSession()->setMultiple($values);
        $this->globals->setFormValuesPrefix($this->formValuesPrefix);
        $this->globals->setTemplateSuffix($this->settings['templateSuffix'] ?? null);
    }

    /**
     * Merges the current GET/POST parameters with the stored ones in SESSION
     */
    protected function mergeGPWithSession()
    {
        if (!is_array($this->gp)) {
            $this->gp = [];
        }
        $values = $this->globals->getSession()->get('values');
        if (!is_array($values)) {
            $values = [];
        }

        $maxStep = $this->currentStep;
        foreach ($values as $step => &$params) {
            if (is_array($params) && (!$maxStep || $step <= $maxStep)) {
                unset($params['submitted']);
                foreach ($params as $key => $value) {
                    if (!isset($this->gp[$key])) {
                        $this->gp[$key] = $value;
                    }
                }
            }
        }
    }

    /**
     * Runs the class by calling process() method.
     */
    protected function runClasses(array $classesArray): ComponentProcessResult
    {
        if (empty($classesArray)) {
            return new ComponentProcessResult();
        }
        if ((int)($this->utilityFuncs->getSingle($classesArray, 'disable')) !== 1) {
            ksort($classesArray);

            //Load language files everytime before running a component. They may have been changed by previous components
            $this->langFiles = $this->utilityFuncs->readLanguageFiles($this->langFiles, $this->settings);
            $this->globals->setLangFiles($this->langFiles);
            foreach ($classesArray as $idx => $tsConfig) {
                if ($idx !== 'disable') {
                    $className = $this->utilityFuncs->getPreparedClassName($tsConfig);
                    if (is_array($tsConfig) && strlen($className) > 0) {
                        if ((int)($this->utilityFuncs->getSingle($tsConfig, 'disable')) !== 1) {
                            /** @var AbstractComponent $obj */
                            $obj = $this->componentManager->getComponent($className);
                            $tsConfig['config.'] = $this->addDefaultComponentConfig($tsConfig['config.'] ?? null);
                            $obj->init($this->gp, $tsConfig['config.']);
                            $obj->validateConfig();
                            $result = $obj->process();
                            if ($result->hasGp()) {
                                //return value is an array. Treat it as the probably modified get/post parameters
                                $this->gp = $result->gp;
                                $this->globals->setGP($this->gp);
                            } elseif ($result->hasResponse()) {
                                //return value is no array. treat this return value as output.
                                return $result;
                            }
                        }
                    } else {
                        $this->utilityFuncs->throwException('classesarray_error');
                    }
                }
            }
        }
        return new ComponentProcessResult();
    }

    /**
     * Find out if submitted form was valid. If one of the values in the given array $valid is false the submission was not valid.
     */
    protected function isValid(array $validArr): bool
    {
        $valid = true;
        if (is_array($validArr)) {
            foreach ($validArr as $idx => $item) {
                if (!$item) {
                    $valid = false;
                }
            }
        }
        return $valid;
    }

    /**
     * Sets the internal attribute "predefined"
     *
     * @param string $key
     */
    public function setPredefined($key): void
    {
        $this->predefined = $key;
    }

    /**
     * Sets the internal attribute "langFile"
     *
     * @param array $langFiles
     */
    public function setLangFiles($langFiles): void
    {
        $this->langFiles = $langFiles;
    }

    /**
     * Sets the template file attribute to $template
     * @param string $template
     */
    public function setTemplateFile($template): void
    {
        $this->templateFile = $template;
    }

    public function getSettings()
    {
        $settings = $this->configuration->getSettings();
        if ($this->predefined && is_array($settings['predef.'][$this->predefined])) {
            $predefSettings = $settings['predef.'][$this->predefined];
            unset($settings['predef.']);
            $settings = $this->utilityFuncs->mergeConfiguration($settings, $predefSettings);
        }
        return $settings;
    }
}
