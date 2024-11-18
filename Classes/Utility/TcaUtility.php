<?php

namespace Typoheads\Formhandler\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Dev-Team Typoheads (dev@typoheads.at)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * UserFunc for rendering of log entry
 */
class TcaUtility
{
    public function getParams($PA, $fobj)
    {
        $params = unserialize($PA['itemFormElValue']);
        $output =
            '<input
			readonly="readonly" style="display:none"
			name="' . $PA['itemFormElName'] . '"
			value="' . htmlspecialchars($PA['itemFormElValue']) . '"
			onchange="' . htmlspecialchars(implode('', $PA['fieldChangeFunc'])) . '"
			' . $PA['onFocus'] . '/>
		';
        $output .= DebugUtility::viewArray($params);
        return $output;
    }

    /**
     * Adds onchange listener on the drop down menu "predefined".
     * If the event is fired and old value was ".default", then empty some fields.
     *
     * @param array $config
     * @return string the javascript
     * @author Fabien Udriot
     */
    public function addFields_predefinedJS($config)
    {
        $newRecord = 'true';
        /** @var ServerRequest $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        $editConf = $request->getQueryParams()['edit']['tt_content'];

        if (is_array($editConf) && reset($editConf) === 'edit') {
            $newRecord = 'false';
        }

        $uid = null;
        if (is_array($editConf)) {
            $uid = key($editConf);
        }
        if ($uid < 0 || empty($uid) || !strstr($uid, 'NEW')) {
            $uid = $GLOBALS['SOBE']->elementsData[0]['uid'];
        }

        $js = "<script>\n";
        $js .= "/*<![CDATA[*/\n";

        $divId = $GLOBALS['SOBE']->tceforms->dynNestedStack[0][1];
        if (!$divId) {
            $divId = 'DIV.c-tablayer';
        } else {
            $divId .= '-DIV';
        }
        $js .= "var uid = '" . $uid . "'\n";
        $js .= "var flexformBoxId = '" . $divId . "'\n";
        $js .= 'var newRecord = ' . $newRecord . "\n";
        $js .= file_get_contents(ExtensionManagementUtility::extPath('formhandler') . 'Resources/Public/JavaScript/addFields_predefinedJS.js');
        $js .= "/*]]>*/\n";
        $js .= "</script>\n";
        return $js;
    }

    /**
     * Sets the items for the "Predefined" dropdown.
     *
     * @param array $config
     * @return array The config including the items for the dropdown
     */
    public function addFields_predefined($config)
    {
        $pid = false;

        /** @var ServerRequest $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        $editConf = $request->getQueryParams()['edit']['tt_content'];

        if (is_array($editConf) && reset($editConf) === 'new') {
            $pid = key($editConf);

            //Formhandler inserted after existing content element
            if ((int)$pid < 0) {
                $conn = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('tt_content');
                $pid = $conn->select(['pid'], 'tt_content', ['uid' => abs($pid)])->fetchOne(0);
            }
        }

        $contentUid = $config['row']['uid'] ?: 0;
        if (!$pid) {
            $conn = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tt_content');
            $row = $conn->select(['pid'], 'tt_content', ['uid' => $contentUid])->fetchAssociative();
            if ($row) {
                $pid = $row['pid'];
            }
        }

        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pid);
        $sitePredefinedForms = $site->getSettings()->getAll()['formhandler']['forms'];

        if (!$sitePredefinedForms) {
            $config['items'] = [
                (new SelectItem(
                    'select',
                    $GLOBALS['LANG']->sL('LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xlf:be_missing_config'),
                    ''
                ))->toArray()
            ];

            return $config;
        }

        $config['items'][] =
            (new SelectItem(
                'select',
                $GLOBALS['LANG']->sL('LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xlf:be_please_select'),
                ''
            ))->toArray();

        foreach ($sitePredefinedForms as $form) {
            $config['items'][] = (new SelectItem(
                'select',
                $form['label'],
                $form['value'],
            ))->toArray();
        }

        return $config;
    }
}
