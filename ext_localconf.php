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

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables']['tx_formhandler_log'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables']['tx_formhandler_log'] = [
        'dateField' => 'tstamp',
        'expirePeriod' => 180,
    ];
}
