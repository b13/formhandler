<?php

return [
    'web_FormhandlerLog' => [
        'parent' => 'web',
        'access' => 'user',
        'iconIdentifier' => 'formhandlerElement',
        'labels' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'Formhandler',
        'controllerActions' => [
            \Typoheads\Formhandler\Controller\ModuleController::class => [
                'index',
                'view',
                'selectFields',
                'export',
            ],
        ],
    ],
];
