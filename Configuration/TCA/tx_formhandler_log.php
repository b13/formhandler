<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xlf:tx_formhandler_log',
        'label' => 'uid',
        'default_sortby' => 'ORDER BY crdate DESC',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'iconfile' => 'EXT:formhandler/Resources/Public/Icons/Extension.gif',
        'adminOnly' => 1,
    ],
    'columns' => [
        'crdate' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xlf:tx_formhandler_log.submission_date',
            'config' => [
                'type' => 'datetime',
                'size' => '10',
                'checkbox' => '0',
                'default' => 0,
            ],
        ],
        'ip' => [
            'label' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xlf:tx_formhandler_log.ip',
            'config' => [
                'type' => 'input',
            ],
        ],
        'params' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xlf:tx_formhandler_log.params',
            'config' => [
                'type' => 'user',
                'userFunc' => 'Typoheads\Formhandler\Utility\TcaUtility->getParams',
            ],
        ],
        'is_spam' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xlf:tx_formhandler_log.is_spam',
            'config' => [
                'type' => 'check',
            ],
        ],
        'language' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xlf:tx_formhandler_log.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => \Typoheads\Formhandler\Utility\TcaSiteLanguagesProcFunc::class . '->getItems',
                'items' => [],
            ],
        ],
        'uid' => [
            'label' => '',
            'config' => [
                'type' => 'none',
            ],
        ],
        'pid' => [
            'label' => '',
            'config' => [
                'type' => 'none',
            ],
        ],
        'tstamp' => [
            'label' => '',
            'config' => [
                'type' => 'none',
            ],
        ],
        'key_hash' => [
            'label' => '',
            'config' => [
                'type' => 'none',
            ],
        ],
        'unique_hash' => [
            'label' => '',
            'config' => [
                'type' => 'none',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'crdate,ip,params,is_spam,language',
        ],
    ],
];
