<?php

namespace Typoheads\Formhandler\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class FormhandlerPluginController extends ActionController
{
    public function indexAction(): ResponseInterface
    {
        $dispatcher = new Dispatcher();
        $dispatcher->setRequests($this->request);
        $content = $dispatcher->main('', []);
        DebuggerUtility::var_dump($content);
        die('ss');
        return $this->htmlResponse($content);
    }

}
