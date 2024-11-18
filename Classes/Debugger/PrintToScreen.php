<?php

namespace Typoheads\Formhandler\Debugger;

use TYPO3\CMS\Core\Utility\DebugUtility;

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
 * A simple debugger printing the messages on the screen
 */
class PrintToScreen extends AbstractDebugger
{
    /**
     * Prints the messages to the screen
     */
    public function outputDebugLog(): void
    {
        $out = '';
        if (!$this->globals->isAjaxMode()) {
            foreach ($this->debugLog as $section => $logData) {
                $out .= $this->globals->getCObj()->wrap($section, $this->utilityFuncs->getSingle($this->settings, 'sectionHeaderWrap'));
                $sectionContent = '';
                foreach ($logData as $messageData) {
                    $message = str_replace("\n", '<br />', $messageData['message']);
                    $message = $this->globals->getCObj()->wrap($message, $this->utilityFuncs->getSingle($this->settings['severityWrap.'], $messageData['severity']));
                    $sectionContent .= $this->globals->getCObj()->wrap($message, $this->settings['messageWrap']);
                    if ($messageData['data']) {
                        $sectionContent .= DebugUtility::viewArray($messageData['data']);
                        $sectionContent .= '<br />';
                    }
                }
                $out .= $this->globals->getCObj()->wrap($sectionContent, $this->utilityFuncs->getSingle($this->settings, 'sectionWrap'));
            }
        }
        print $out;
    }

    /**
     * Sets default config for the debugger.
     */
    public function validateConfig(): void
    {
        if (!isset($this->settings['sectionWrap']) || !$this->settings['sectionWrap']) {
            $this->settings['sectionWrap'] = '<div style="border:1px solid #ccc; padding:7px; background:#dedede;">|</div>';
        }
        if (!isset($this->settings['sectionHeaderWrap']) || !$this->settings['sectionHeaderWrap']) {
            $this->settings['sectionHeaderWrap'] = '<h2 style="background:#333; color:#cdcdcd;height:23px;padding:10px 7px 7px 7px;margin:0;">|</h2>';
        }
        if (!isset($this->settings['messageWrap']) || !$this->settings['messageWrap']) {
            $this->settings['messageWrap'] = '<div style="font-weight:bold;">|</div>';
        }

        $this->settings['severityWrap'] = [
            '1' => '<span style="color:#000;">|</span>',
            '2' => '<span style="color:#FF8C00;">|</span>',
            '3' => '<span style="color:#FF8C00;">|</span>',
        ];

    }
}
