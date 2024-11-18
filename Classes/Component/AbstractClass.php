<?php

namespace Typoheads\Formhandler\Component;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Typoheads\Formhandler\Controller\Configuration;
use Typoheads\Formhandler\Utility\GeneralUtility;
use Typoheads\Formhandler\Utility\Globals;

/*                                                                       *
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
 * Abstract class for any usable Formhandler component.
 * This class defines some useful variables and a default constructor for all Formhandler components.
 * @abstract
 */
abstract class AbstractClass
{
    protected Manager $componentManager;
    protected Configuration $configuration;
    protected Globals $globals;
    protected GeneralUtility $utilityFuncs;

    /**
     * The cObj
     *
     * @var ContentObjectRenderer
     */
    protected $cObj;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $validationStatusClasses;

    /**
     * The constructor for an interceptor setting the component manager and the configuration.
     *
     * @param Manager $componentManager
     * @param Configuration $configuration
     */
    public function __construct(
    ) {
        $this->componentManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Manager::class);
        $this->configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Configuration::class);
        $this->globals = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Globals::class);
        $this->utilityFuncs = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(GeneralUtility::class);
        $this->cObj = $this->globals->getCObj();
    }
}
