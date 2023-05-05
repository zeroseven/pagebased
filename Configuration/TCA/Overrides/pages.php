<?php

defined('TYPO3') || die('🤬 F**k off!');

call_user_func(static function (string $table) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [
        '_rampage_top' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages._rampage_top',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled', 1]
                ],
                'default' => 0
            ]
        ],
        '_rampage_date' => [
            'exclude' => false,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages._rampage_date',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 10,
                'eval' => 'date,required',
                'default' => time()
            ]
        ],
        '_rampage_tags' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages._rampage_tags',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'user',
                'renderType' => 'rampageTags',
                'placeholder' => 'ADD TAGS …',
                'default' => ''
            ]
        ],
        '_rampage_topics' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages._rampage_topics',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'foreign_table' => 'tx_rampage_domain_model_topic',
                'MM' => 'tx_rampage_object_topic_mm',
                'itemsProcFunc' => \Zeroseven\Rampage\Backend\TCA\ItemsProcFunc::class . '->topics',
                'default' => 0
            ]
        ],
        '_rampage_relations_to' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages._rampage_relations_to',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => $table,
                'foreign_table' => $table,
                'MM_opposite_field' => '_rampage_relations_from',
                'MM' => 'tx_rampage_relation_mm',
                'size' => 5,
                'autoSizeMax' => 10,
                'maxitems' => 99,
                'filter' => [
                    [
                        'userFunc' => \Zeroseven\Rampage\Backend\TCA\GroupFilter::class . '->filterObject'
                    ]
                ],
                'suggestOptions' => [
                    'default' => [
                        'receiverClass' => \Zeroseven\Rampage\Backend\Form\Wizard\SuggestRelationReceiver::class,
                        'minimumCharacters' => 2,
                        'searchWholePhrase' => true,
                    ],
                    $table => [
                        'addWhere' => ' AND ' . $table . '.uid != ###THIS_UID###'
                    ]
                ],
                'elementBrowserEntryPoints' => [
                    $table => '###CURRENT_PID###'
                ]
            ]
        ],
        '_rampage_relations_from' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages._rampage_relations_from',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'foreign_table' => $table,
                'allowed' => $table,
                'size' => 5,
                'maxitems' => 100,
                'MM' => 'tx_rampage_relation_mm',
                'readOnly' => 1
            ]
        ],
        '_rampage_redirect_category' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages._rampage_redirect_category',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled', 1]
                ],
                'default' => 0
            ]
        ],

        // System relevant fields
        \Zeroseven\Rampage\Utility\IdentifierUtility::SITE_FIELD_NAME => [
            'exclude' => false,
            'l10n_mode' => 'exclude',
            'label' => 'Site',
            'displayCond' => [
                'AND' => [
                    'HIDE_FOR_NON_ADMINS',
                    'FIELD:_rampage_site_identifier:REQ:true'
                ]
            ],
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'default' => ''
            ]
        ],
        \Zeroseven\Rampage\Utility\IdentifierUtility::OBJECT_FIELD_NAME => [
            'exclude' => false,
            'l10n_mode' => 'exclude',
            'label' => 'Object',
            'displayCond' => [
                'AND' => [
                    'HIDE_FOR_NON_ADMINS',
                    'FIELD:_rampage_object_identifier:REQ:true'
                ]
            ],
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'default' => ''
            ]
        ],
        'pid' => [
            'config' => [
                'type' => 'passthrough',
                'foreign_table' => $table,
            ]
        ],
        'SYS_LASTCHANGED' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ]
    ]);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($table, '--div--;OPTIONS, _rampage_top, _rampage_date, _rampage_tags, _rampage_topics, _rampage_relations_to, _rampage_relations_from, _rampage_redirect_category, _rampage_site_identifier, _rampage_object_identifier', '', 'after:title');
}, 'pages');
