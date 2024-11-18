<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Ajax\Validate;

class AjaxValidate implements MiddlewareInterface
{
    public const NAMESPACE = 'tx-formhandler-ajax-validate';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var PageArguments $pageArguments */
        $pageArguments = $request->getAttribute('routing');
        $arguments = $pageArguments->getArguments();
        if (isset($arguments[self::NAMESPACE])) {
            $validator = GeneralUtility::makeInstance(Validate::class);
            $content = $validator->main($request);
            return new HtmlResponse($content);
        }

        return $handler->handle($request);
    }
}
