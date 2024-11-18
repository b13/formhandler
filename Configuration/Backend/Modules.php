<?php

return [
    'web_FormhandlerLog' => [
        'parent' => 'web',
        'access' => 'user',
        'iconIdentifier' => null,
        'labels' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'Formhandler',
        'controllerActions' => [
            'Typoheads\Formhandler\Controller\ModuleController' => [
                'index',
                'view',
                'selectFields',
                'export',
            ],
        ],
    ],
];
