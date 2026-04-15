<?php

namespace Typoheads\Formhandler\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class FormhandlerPluginController extends ActionController
{
    public function indexAction(): ResponseInterface
    {
        $dispatcher = new Dispatcher();
        $dispatcher->setRequests($this->request);
        $typoscript = GeneralUtility::makeInstance(TypoScriptService::class);
        $settings = $typoscript->convertPlainArrayToTypoScriptArray($this->settings);
        return $dispatcher->main($settings);
    }

}
