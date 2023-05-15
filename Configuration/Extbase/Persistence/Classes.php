<?php

return [
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
            'date' => [
                'fieldName' => '_rampage_date'
            ],
            'tagsString' => [
                'fieldName' => '_rampage_tags'
            ],
            'topics' => [
                'fieldName' => '_rampage_topics'
            ],
            'contact' => [
                'fieldName' => '_rampage_contact'
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
