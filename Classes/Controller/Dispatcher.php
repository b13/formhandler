<?php

namespace Typoheads\Formhandler\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Plugin\AbstractPlugin;
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
 * The Dispatcher instantiates the Component Manager and delegates the process to the given controller.
 */
class Dispatcher extends AbstractPlugin
{
    /**
     * Compontent Manager
     *
     * @var Manager
     */
    protected $componentManager;

    /**
     * The global Formhandler values
     *
     * @var Globals
     */
    protected $globals;

    /**
     * The Formhandler utility functions
     *
     * @var \Typoheads\Formhandler\Utility\GeneralUtility
     */
    protected $utilityFuncs;

    public function main(): ResponseInterface
    {
        $this->componentManager = GeneralUtility::makeInstance(Manager::class);
        $this->globals = GeneralUtility::makeInstance(Globals::class);
        $this->utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);

        //init flexform
        $this->pi_initPIflexForm();
        $this->pi_loadLL();

        /*
         * Parse values from flexform:
         * - Template file
         * - Translation file
         * - Predefined form
         * - E-mail settings
         * - Required fields
         * - Redirect page
         */
        $templateFile = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_file', 'sDEF');
        $langFile = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'lang_file', 'sDEF');
        $predef = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'predefined', 'sDEF');

        $this->globals->setCObj($this->cObj);
        $this->globals->setRequest($this->request);
        $this->globals->getCObj()->setCurrentVal($predef);
        $this->globals->setPredef($predef);

        $controller = GeneralUtility::makeInstance(FormController::class);

        if (strlen($templateFile) > 0) {
            $controller->setTemplateFile($templateFile);
        }
        if (strlen($langFile) > 0) {
            $controller->setLangFiles([$langFile]);
        }
        if (strlen($predef) > 0) {
            $controller->setPredefined($predef);
        }

        $result = $controller->process();
        return $result;
    }
}
