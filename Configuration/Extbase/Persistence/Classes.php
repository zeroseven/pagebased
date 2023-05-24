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
    \Zeroseven\Rampage\Domain\Model\AbstractObject::class => [
        'tableName' => \Zeroseven\Rampage\Domain\Model\AbstractPage::TABLE_NAME,
        'properties' => [
            'top' => [
                'fieldName' => 'rampage_top'
            ],
            'date' => [
                'fieldName' => 'rampage_date'
            ],
            'tagsString' => [
                'fieldName' => 'rampage_tags'
            ],
            'topics' => [
                'fieldName' => 'rampage_topics'
            ],
            'contact' => [
                'fieldName' => 'rampage_contact'
            ],
            'relationsTo' => [
                'fieldName' => 'rampage_relations_to'
            ],
            'relationsFrom' => [
                'fieldName' => 'rampage_relations_from'
            ]
        ]
    ]
];
