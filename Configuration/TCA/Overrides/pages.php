<?php

defined('TYPO3') || die('🤬 F**k off!');

call_user_func(static function (string $table) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [
        'rampage_top' => [
            'exclude' => false,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages.rampage_top',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled', 1]
                ],
                'default' => 0
            ]
        ],
        'rampage_date' => [
            'exclude' => false,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages.rampage_date',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 10,
                'eval' => 'date,required',
                'default' => time()
            ]
        ],
        'rampage_tags' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages.rampage_tags',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'user',
                'renderType' => 'rampageTags',
                'placeholder' => 'ADD TAGS …',
                'default' => ''
            ]
        ],
        'rampage_topics' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages.rampage_topics',
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
        'rampage_contact' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages.rampage_contact',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_rampage_domain_model_contact',
                'itemsProcFunc' => \Zeroseven\Rampage\Backend\TCA\ItemsProcFunc::class . '->contacts',
                'default' => 0,
                'items' => [
                    ['-', 0, 'actions-user']
                ]
            ]
        ],
        'rampage_relations_to' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages.rampage_relations_to',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => $table,
                'foreign_table' => $table,
                'MM_opposite_field' => 'rampage_relations_from',
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
        'rampage_relations_from' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages.rampage_relations_from',
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
        'rampage_redirect_category' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages.rampage_redirect_category',
            'description' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages.rampage_redirect_category.description',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled', 1]
                ],
                'default' => 0
            ]
        ]
    ]);

    // System relevant fields must exist in TCA to map their values to the model
    foreach ([
                 'SYS_LASTCHANGED', 'crdate',
                 \Zeroseven\Rampage\Utility\DetectionUtility::SITE_FIELD_NAME,
                 \Zeroseven\Rampage\Utility\DetectionUtility::REGISTRATION_FIELD_NAME,
                 \Zeroseven\Rampage\Utility\DetectionUtility::CHILD_OBJECT_FIELD_NAME
             ] as $fieldName) {
        !isset($GLOBALS['TCA'][$table]['columns'][$fieldName])
        || \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [$fieldName => ['config' => ['type' => 'passthrough']]]);
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($table, '--div--;LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages.tab.rampage_settings, rampage_top, rampage_date, rampage_tags, rampage_topics, rampage_contact, rampage_relations_to, rampage_relations_from, rampage_redirect_category,' . implode(',', [
            \Zeroseven\Rampage\Utility\DetectionUtility::SITE_FIELD_NAME,
            \Zeroseven\Rampage\Utility\DetectionUtility::REGISTRATION_FIELD_NAME,
            \Zeroseven\Rampage\Utility\DetectionUtility::CHILD_OBJECT_FIELD_NAME
        ]), '');
}, 'pages');
