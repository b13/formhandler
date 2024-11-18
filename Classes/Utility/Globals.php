<?php

namespace Typoheads\Formhandler\Utility;

use TYPO3\CMS\Core\SingletonInterface;

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
 * A helper class for Formhandler to store global values
 */
class Globals implements SingletonInterface
{
    protected static $ajaxHandler;
    protected static $ajaxMode;
    protected static $cObj;
    protected static $debuggers;
    protected static $formID;
    protected static $formValuesPrefix;
    protected static $gp;
    protected static $langFiles;
    protected static $overrideSettings;
    protected static $predef;
    protected static $randomID;
    protected static $session;
    protected static $settings;
    protected static $submitted;
    protected static $templateCode;
    protected static $templateSuffix;

    public static function setAjaxMode($mode): void
    {
        self::$ajaxMode = $mode;
    }

    public static function isAjaxMode()
    {
        return self::$ajaxMode;
    }

    public static function setAjaxHandler($ajaxHandler): void
    {
        self::$ajaxHandler = $ajaxHandler;
    }

    public static function setCObj($cObj): void
    {
        self::$cObj = $cObj;
    }

    public static function setDebuggers($debuggers): void
    {
        self::$debuggers = $debuggers;
    }

    public static function addDebugger($debugger): void
    {
        if (!is_array(self::$debuggers)) {
            self::$debuggers = [];
        }
        self::$debuggers[] = $debugger;
    }

    public static function setFormID($formID): void
    {
        self::$formID = $formID;
    }

    public static function setFormValuesPrefix($formValuesPrefix): void
    {
        self::$formValuesPrefix = $formValuesPrefix;
    }

    public static function setGP($gp): void
    {
        self::$gp = $gp;
    }

    public static function setLangFiles($langFiles): void
    {
        self::$langFiles = $langFiles;
    }

    public static function setOverrideSettings($overrideSettings): void
    {
        self::$overrideSettings = $overrideSettings;
    }

    public static function setPredef($predef): void
    {
        self::$predef = $predef;
    }

    public static function setRandomID($randomID): void
    {
        self::$randomID = $randomID;
    }

    public static function setSession($session): void
    {
        self::$session = $session;
    }

    public static function setSettings($settings): void
    {
        self::$settings = $settings;
    }

    public static function setSubmitted($submitted): void
    {
        self::$submitted = $submitted;
    }

    public static function setTemplateCode($templateCode): void
    {
        self::$templateCode = $templateCode;
    }

    public static function setTemplateSuffix($templateSuffix): void
    {
        self::$templateSuffix = $templateSuffix;
    }

    public static function getAjaxHandler()
    {
        return self::$ajaxHandler;
    }

    public static function getCObj()
    {
        return self::$cObj;
    }

    public static function getDebuggers()
    {
        if (!is_array(self::$debuggers)) {
            self::$debuggers = [];
        }
        return self::$debuggers;
    }

    public static function getFormID()
    {
        return self::$formID;
    }

    public static function getFormValuesPrefix()
    {
        return self::$formValuesPrefix;
    }

    public static function getGP()
    {
        if (!is_array(self::$gp)) {
            self::$gp = [];
        }
        return self::$gp;
    }

    public static function getLangFiles()
    {
        if (!is_array(self::$langFiles)) {
            self::$langFiles = [];
        }
        return self::$langFiles;
    }

    public static function getOverrideSettings()
    {
        if (!is_array(self::$overrideSettings)) {
            self::$overrideSettings = [];
        }
        return self::$overrideSettings;
    }

    public static function getPredef()
    {
        return self::$predef;
    }

    public static function getRandomID()
    {
        return self::$randomID;
    }

    public static function getSession()
    {
        return self::$session;
    }

    public static function getSettings()
    {
        if (!is_array(self::$settings)) {
            self::$settings = [];
        }
        return self::$settings;
    }

    public static function isSubmitted()
    {
        return self::$submitted;
    }

    public static function getTemplateCode()
    {
        return self::$templateCode;
    }

    public static function getTemplateSuffix()
    {
        return self::$templateSuffix;
    }
}
