<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Utility;

use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Site\SiteFinder;

class TcaSiteLanguagesProcFunc
{
    public function __construct(protected SiteFinder $siteFinder) {}

    public function getItems(array &$parameters): void
    {
        $pageId = (int)$parameters['row']['pid'];
        try {
            $site = $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException $e) {
            return;
        }
        $languages = $site->getLanguages();
        $items = [];
        $item = new SelectItem(
            'select',
            '-',
            '-1'
        );
        $items[] = $item;
        foreach ($languages as $language) {
            $item = new SelectItem(
                'select',
                $language->getTitle(),
                $language->getLanguageId(),
                $language->getFlagIdentifier()
            );
            $items[] = $item;
        }
        $parameters['items'] = $items;
    }
}
