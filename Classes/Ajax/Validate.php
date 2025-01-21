<?php

namespace Typoheads\Formhandler\Ajax;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Utility\Globals;
use Typoheads\Formhandler\View\AjaxValidation;

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
*
*                                                                        */
/**
 * A class validating a field via AJAX.
 */
class Validate
{
    /**
     * @var array
     */
    protected $templates = [
        'spanSuccess' => '<span class="success">%s</span>',
        'spanError' => '<span class="error">%s</span>',
    ];

    /**
     * @var Manager
     */
    private $componentManager;

    /**
     * @var array
     */
    private $settings;

    /**
     * Main method of the class.
     *
     * @return string The HTML list of remaining files to be displayed in the form
     */
    public function main(ServerRequest $request)
    {

        $this->componentManager = GeneralUtility::makeInstance(Manager::class);
        Globals::setAjaxMode(true);

        /** @var TypoScriptFrontendController */
        $controller = $request->getAttribute('frontend.controller');

        $field = htmlspecialchars((string)($GLOBALS['TYPO3_REQUEST']->getParsedBody()['field'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['field'] ?? null));
        if ($field) {
            $randomID = htmlspecialchars((string)($GLOBALS['TYPO3_REQUEST']->getParsedBody()['randomID'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['randomID'] ?? null));
            Globals::setCObj($GLOBALS['TSFE']->cObj);
            Globals::setRandomID($randomID);
            if (!Globals::getSession()) {
                $ts = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.typoscript')->getSetupArray()['plugin.']['Tx_Formhandler.']['settings.'];
                $sessionClass = \Typoheads\Formhandler\Utility\GeneralUtility::getPreparedClassName($ts['session.'] ?? null, 'Session\PHP');
                Globals::setSession($this->componentManager->getComponent($sessionClass));
            }
            $this->settings = Globals::getSession()->get('settings');
            Globals::setFormValuesPrefix(\Typoheads\Formhandler\Utility\GeneralUtility::getSingle($this->settings, 'formValuesPrefix'));
            $gp = \Typoheads\Formhandler\Utility\GeneralUtility::getMergedGP();
            $validator = $this->componentManager->getComponent('\Typoheads\Formhandler\Validator\Ajax');
            $errors = [];
            $valid = $validator->validateAjax($field, $gp, $errors);

            if ($valid) {
                $content = \Typoheads\Formhandler\Utility\GeneralUtility::getSingle($this->settings['ajax.']['config.'] ?? null, 'ok');
                if (strlen($content) === 0) {
                    $content = '<img src="' . PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath('formhandler')) . 'Resources/Public/Images/ok.png' . '" />';
                } else {
                    $gp = [
                        $_GET['field'] => $_GET['value'] ?? null,
                    ];
                    $view = $this->initView($content);
                    $content = $view->render($gp, $errors);
                }
                $content = sprintf($this->templates['spanSuccess'], $content);
            } else {
                $content = \Typoheads\Formhandler\Utility\GeneralUtility::getSingle($this->settings['ajax.']['config.'], 'notOk');
                if (strlen($content) === 0) {
                    $content = '<img src="' . PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath('formhandler')) . 'Resources/Public/Images/notok.png' . '" />';
                } else {
                    $view = $this->initView($content);
                    $gp = [
                        $_GET['field'] => $_GET['value'] ?? null,
                    ];
                    $content = $view->render($gp, $errors);
                }
                $content = sprintf($this->templates['spanError'], $content);
            }
            return $content;
        }
        return '';
    }

    /**
     * Initialize the AJAX validation view.
     *
     * @param string $content The raw content
     * @return AjaxValidation The view class
     */
    protected function initView($content)
    {
        $viewClass = '\Typoheads\Formhandler\View\AjaxValidation';
        $view = $this->componentManager->getComponent($viewClass);
        $view->setLangFiles(\Typoheads\Formhandler\Utility\GeneralUtility::readLanguageFiles([], $this->settings));
        $view->setSettings($this->settings);
        $templateName = 'AJAX';
        $template = str_replace('###fieldname###', htmlspecialchars($_GET['field']), $content);
        $template = '###TEMPLATE_' . $templateName . '###' . $template . '###TEMPLATE_' . $templateName . '###';
        $view->setTemplate($template, 'AJAX');
        return $view;
    }
}
