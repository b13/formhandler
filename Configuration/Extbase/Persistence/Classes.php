<?php

declare(strict_types=1);

use Typoheads\Formhandler\Domain\Model\Demand;
use Typoheads\Formhandler\Domain\Model\LogData;

return [
    LogData::class => [
        'tableName' => 'tx_formhandler_log',
        'properties' => [
            'crdate' => [
                'fieldName' => 'crdate',
            ],
            'isSpam' => [
                'fieldName' => 'isSpam',
            ],
            'params' => [
                'fieldName' => 'params',
            ],
            'ip' => [
                'fieldName' => 'ip',
            ],
        ],
    ],
    Demand::class => [
        'tableName' => 'tx_formhandler_log',
        'properties' => [
            'isSpam' => [
                'fieldName' => 'is_spam',
            ],
        ],
    ],
];
