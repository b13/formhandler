<?php

use Typoheads\Formhandler\Middleware\AjaxValidate;

return [

    'frontend' => [
        'formhandler-ajax-validate' => [
            'target' => AjaxValidate::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
    ],
];
