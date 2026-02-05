<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(
    function () {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Formhandler',
            'Pi1',
            [\Typoheads\Formhandler\Controller\FormhandlerPluginController::class => 'index'],
            [\Typoheads\Formhandler\Controller\FormhandlerPluginController::class => 'index'],
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
    }
);

//Hook in tslib_content->stdWrap
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']['formhandler'] = 'Typoheads\Formhandler\Hooks\StdWrapHook';

// load default PageTS config from static file
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:formhandler/Configuration/TypoScript/pageTsConfig.ts">');

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables']['tx_formhandler_log'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables']['tx_formhandler_log'] = [
        'dateField' => 'tstamp',
        'expirePeriod' => 180,
    ];
}

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Imaging\IconRegistry::class
);
$iconRegistry->registerIcon(
    'formhandler-foldericon',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:formhandler/Resources/Public/Images/pagetreeicon.png']
);

if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['Typoheads']['Formhandler']['writerConfiguration'])) {
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['Typoheads']['Formhandler']['writerConfiguration'] = [
        \TYPO3\CMS\Core\Log\LogLevel::ERROR => [
            \TYPO3\CMS\Core\Log\Writer\SyslogWriter::class => [],
        ],
    ];
}
