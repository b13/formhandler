<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}
// REGISTER ICONS FOR USE IN BACKEND WIZARD
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'formhandlerElement',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:formhandler/Resources/Public/Icons/Extension.gif']
);
