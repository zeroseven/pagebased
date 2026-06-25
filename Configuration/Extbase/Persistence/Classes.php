<?php

declare(strict_types=1);

use Zeroseven\Pagebased\Domain\Model\AbstractCategory;
use Zeroseven\Pagebased\Domain\Model\AbstractObject;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Domain\Model\Entity\PageObject;

return [
    PageObject::class => [
        'tableName' => AbstractPage::TABLE_NAME,
    ],
    AbstractPage::class => [
        'tableName' => AbstractPage::TABLE_NAME,
        'properties' => [
            'fileReferences' => [
                'fieldName' => 'media',
            ],
            'documentType' => [
                'fieldName' => 'doktype',
            ],
            'navigationTitle' => [
                'fieldName' => 'nav_title',
            ],
            'lastChangeDate' => [
                'fieldName' => 'SYS_LASTCHANGED',
            ],
            'createDate' => [
                'fieldName' => 'crdate',
            ],
            'accessStartDate' => [
                'fieldName' => 'starttime',
            ],
            'accessEndDate' => [
                'fieldName' => 'endtime',
            ],
        ],
    ],
    AbstractObject::class => [
        'tableName' => AbstractPage::TABLE_NAME,
        'properties' => [
            'top' => [
                'fieldName' => 'pagebased_top',
            ],
            'date' => [
                'fieldName' => 'pagebased_date',
            ],
            'tagsString' => [
                'fieldName' => 'pagebased_tags',
            ],
            'topics' => [
                'fieldName' => 'pagebased_topics',
            ],
            'contact' => [
                'fieldName' => 'pagebased_contact',
            ],
            'relationsTo' => [
                'fieldName' => 'pagebased_relations_to',
            ],
            'relationsFrom' => [
                'fieldName' => 'pagebased_relations_from',
            ],
            'childObject' => [
                'fieldName' => '_pagebased_child_object',
            ],
        ],
    ],
    AbstractCategory::class => [
        'tableName' => AbstractPage::TABLE_NAME,
        'properties' => [
            'redirectCategory' => [
                'fieldName' => 'pagebased_redirect_category',
            ],
        ],
    ],
];
