<?php

namespace Typoheads\Formhandler\Component;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 * Component Manager originally written for the extension 'gimmefive'.
 * This is a backport of the Component Manager of FLOW3. It's based
 * on code mainly written by Robert Lemke. Thanx to the FLOW3 team for all the great stuff!
 *
 * Refactored for usage with Formhandler.
 */
class Manager implements SingletonInterface
{
    /**
     * The global Formhandler values
     *
     * @var Globals
     */
    protected $globals;

    /**
     * The global Formhandler values
     *
     * @var \Typoheads\Formhandler\Utility\GeneralUtility
     */
    protected $utilityFuncs;

    public function __construct()
    {
        $this->globals = GeneralUtility::makeInstance(Globals::class);
        $this->utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);
    }

    /**
     * Returns a component object from the cache. If there is no object stored already, a new one is created and stored in the cache.
     *
     * @param string $componentName
     * @return mixed
     * @author Robert Lemke <robert@typo3.org>
     * @author adapted for TYPO3v4 by Jochen Rau <jochen.rau@typoplanet.de>
     */
    public function getComponent(string $componentName)
    {
        $componentName = $this->utilityFuncs->prepareClassName($componentName);
        //Avoid component manager creating multiple instances of itself
        if (static::class === $componentName) {
            return $this;
        }
        $arguments = array_slice(func_get_args(), 1, null, true);

        return GeneralUtility::makeInstance($componentName, $arguments);
    }
}
