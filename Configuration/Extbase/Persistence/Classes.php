<?php

return [
    \Zeroseven\Rampage\Domain\Model\Entity\ParentPage::class => [
        'tableName' => \Zeroseven\Rampage\Domain\Model\AbstractPage::TABLE_NAME,
    ],
    \Zeroseven\Rampage\Domain\Model\Entity\PageObject::class => [
        'tableName' => \Zeroseven\Rampage\Domain\Model\AbstractPage::TABLE_NAME,
    ],
    \Zeroseven\Rampage\Domain\Model\AbstractPage::class => [
        'tableName' => \Zeroseven\Rampage\Domain\Model\AbstractPage::TABLE_NAME,
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
        'tableName' => \Zeroseven\Rampage\Domain\Model\AbstractPage::TABLE_NAME,
        'properties' => [
            'top' => [
                'fieldName' => '_rampage_top'
            ],
            'tags' => [
                'fieldName' => '_rampage_tags'
            ],
            'relationsTo' => [
                'fieldName' => '_rampage_relations_to'
            ],
            'relationsFrom' => [
                'fieldName' => '_rampage_relations_from'
            ]
        ]
    ]
];
