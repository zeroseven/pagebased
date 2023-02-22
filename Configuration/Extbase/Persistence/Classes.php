<?php

return [
    \Zeroseven\Rampage\Domain\Model\Entity\ParentPage::class => [
        'tableName' => 'pages',
    ],
    \Zeroseven\Rampage\Domain\Model\Entity\PageObject::class => [
        'tableName' => 'pages',
    ],
    \Zeroseven\Rampage\Domain\Model\AbstractPage::class => [
        'tableName' => 'pages',
        'properties' => [
            'fileReferences' => [
                'fieldName' => 'media'
            ],
            'documentType' => [
                'fieldName' => 'doktype'
            ],
            'navigationTitle' => [
                'fieldName' => 'nav_title'
            ],
            'parentPage' => [
                'fieldName' => 'pid'
            ],
            'lastChange' => [
                'fieldName' => 'SYS_LASTCHANGED'
            ]
        ]
    ],
    \Zeroseven\Rampage\Domain\Model\AbstractPageObject::class => [
        'tableName' => 'pages',
        'properties' => [
            'fileReferences' => [
                'fieldName' => 'media'
            ],
            'lastChange' => [
                'fieldName' => 'SYS_LASTCHANGED'
            ]
        ]
    ]
];
