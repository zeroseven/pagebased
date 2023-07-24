<?php

return [
    \Zeroseven\Pagebased\Domain\Model\Entity\PageObject::class => [
        'tableName' => \Zeroseven\Pagebased\Domain\Model\AbstractPage::TABLE_NAME,
    ],
    \Zeroseven\Pagebased\Domain\Model\AbstractPage::class => [
        'tableName' => \Zeroseven\Pagebased\Domain\Model\AbstractPage::TABLE_NAME,
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
            'lastChangeDate' => [
                'fieldName' => 'SYS_LASTCHANGED'
            ],
            'createDate' => [
                'fieldName' => 'crdate'
            ],
            'accessStartDate' => [
                'fieldName' => 'starttime'
            ],
            'accessEndDate' => [
                'fieldName' => 'endtime'
            ]
        ]
    ],
    \Zeroseven\Pagebased\Domain\Model\AbstractObject::class => [
        'tableName' => \Zeroseven\Pagebased\Domain\Model\AbstractPage::TABLE_NAME,
        'properties' => [
            'top' => [
                'fieldName' => 'pagebased_top'
            ],
            'date' => [
                'fieldName' => 'pagebased_date'
            ],
            'tagsString' => [
                'fieldName' => 'pagebased_tags'
            ],
            'topics' => [
                'fieldName' => 'pagebased_topics'
            ],
            'contact' => [
                'fieldName' => 'pagebased_contact'
            ],
            'relationsTo' => [
                'fieldName' => 'pagebased_relations_to'
            ],
            'relationsFrom' => [
                'fieldName' => 'pagebased_relations_from'
            ]
        ]
    ],
    \Zeroseven\Pagebased\Domain\Model\AbstractCategory::class => [
        'tableName' => \Zeroseven\Pagebased\Domain\Model\AbstractPage::TABLE_NAME,
        'properties' => [
            'redirectCategory' => [
                'fieldName' => 'pagebased_redirect_category'
            ]
        ]
    ]
];
