<?php

namespace Typoheads\Formhandler\View;

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Controller\Configuration;
use Typoheads\Formhandler\Utility\GeneralUtility;
use Typoheads\Formhandler\Utility\Globals;

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
 * An abstract view for Formhandler
 */
abstract class AbstractView extends AbstractPlugin
{
    /**
     * The prefix id
     *
     * @var string
     */
    public $prefixId = 'Tx_Formhandler';

    /**
     * The extension key
     *
     * @var string
     */
    public $extKey = 'formhandler';

    protected ?ContentObjectRenderer $cObj = null;

    /**
     * @var MarkerBasedTemplateService
     */
    protected $markerBasedTemplateService;

    /**
     * The piVars
     *
     * @var array
     */
    public $piVars;

    protected Manager $componentManager;
    protected Configuration $configuration;
    protected Globals $globals;
    protected GeneralUtility $utilityFuncs;

    /**
     * The model of the view
     *
     * @var misc
     */
    protected $model;

    /**
     * The subparts array
     *
     * @var array
     */
    protected $subparts;

    /**
     * The template code
     *
     * @var string
     */
    protected $template;

    /**
     * An array of translation file names
     *
     * @var array
     */
    protected $langFiles;

    /**
     * The get/post parameters
     *
     * @var array
     */
    protected $gp;

    protected $componentSettings;

    public function __construct(
    ) {
        parent::__construct();
        $this->componentManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Manager::class);
        $this->configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Configuration::class);
        $this->globals = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Globals::class);
        $this->utilityFuncs = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(GeneralUtility::class);
        $this->cObj = $this->globals->getCObj();
        $this->markerBasedTemplateService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $this->pi_loadLL();
        $this->initializeView();
    }

    /**
     * Sets the internal attribute "langFiles"
     *
     * @param array $langFiles The files array
     */
    public function setLangFiles($langFiles): void
    {
        $this->langFiles = $langFiles;
    }

    /**
     * Sets the settings
     *
     * @param string $settings The settings
     */
    public function setSettings($settings): void
    {
        $this->settings = $settings;
    }

    public function setComponentSettings($settings): void
    {
        $this->componentSettings = $settings;
    }

    public function getComponentSettings()
    {
        if (!is_array($this->componentSettings)) {
            $this->componentSettings = [];
        }
        return $this->componentSettings;
    }

    /**
     * Sets the key of the chosen predefined form
     *
     * @param string $key The key of the predefined form
     */
    public function setPredefined($key): void
    {
        $this->predefined = $key;
    }

    /**
     * Sets the model of the view
     *
     * @param misc $model
     */
    public function setModel($model): void
    {
        $this->model = $model;
    }

    /**
     * Returns the model of the view
     *
     * @return misc $model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the template of the view
     *
     * @param string $templateCode The whole template code of a template file
     * @param string $templateName Name of a subpart containing the template code to work with
     * @param bool $forceTemplate Not needed
     */
    public function setTemplate($templateCode, $templateName, $forceTemplate = false): void
    {
        $this->subparts['template'] = $this->markerBasedTemplateService->getSubpart($templateCode, '###TEMPLATE_' . $templateName . '###');
        $this->subparts['item'] = $this->markerBasedTemplateService->getSubpart($this->subparts['template'], '###ITEM###');
    }

    /**
     * Returns false if the view doesn't have template code.
     *
     * @return bool
     */
    public function hasTemplate()
    {
        return !empty($this->subparts['template']);
    }

    /**
     * This method performs the rendering of the view
     *
     * @param array $gp The get/post parameters
     * @param array $errors An array with errors occurred whilest validation
     * @return rendered view
     * @abstract
     */
    abstract public function render($gp, $errors);

    /**
     * Overwrite this method to extend the initialization of the View
     *
     * @author Jochen Rau
     */
    protected function initializeView() {}

    /**
     * Returns given string in uppercase
     *
     * @param string $camelCase The string to transform
     * @return string Parsed string
     * @author Jochen Rau
     */
    protected function getUpperCase($camelCase)
    {
        return strtoupper(preg_replace('/\p{Lu}+(?!\p{Ll})|\p{Lu}/u', '_$0', $camelCase));
    }
}
