<?php

namespace Typoheads\Formhandler\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class FormhandlerPluginController extends ActionController
{
    public function indexAction(): ResponseInterface
    {
        $dispatcher = new Dispatcher();
        $dispatcher->setRequests($this->request);
        $content = $dispatcher->main('', []);
        return $this->htmlResponse($content);
    }

}
