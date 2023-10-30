<?php

defined('TYPO3') || die('📄');

call_user_func(static function (string $table) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [
        'pagebased_top' => [
            'exclude' => false,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:pagebased/Resources/Private/Language/locallang_db.xlf:pages.pagebased_top',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'check',
                'items' => [[
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled',
                    'value' => 1
                ]],
                'default' => 0
            ]
        ],
        'pagebased_date' => [
            'exclude' => false,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:pagebased/Resources/Private/Language/locallang_db.xlf:pages.pagebased_date',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'required' => true,
                'default' => time()
            ]
        ],
        'pagebased_tags' => [
            'exclude' => true,
            'label' => 'LLL:EXT:pagebased/Resources/Private/Language/locallang_db.xlf:pages.pagebased_tags',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'user',
                'renderType' => 'pagebasedTags',
                'placeholder' => 'ADD TAGS …',
                'default' => ''
            ]
        ],
        'pagebased_topics' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:pagebased/Resources/Private/Language/locallang_db.xlf:pages.pagebased_topics',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'foreign_table' => 'tx_pagebased_domain_model_topic',
                'MM' => 'tx_pagebased_object_topic_mm',
                'itemsProcFunc' => \Zeroseven\Pagebased\Backend\TCA\ItemsProcFunc::class . '->topics',
                'default' => 0
            ]
        ],
        'pagebased_contact' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:pagebased/Resources/Private/Language/locallang_db.xlf:pages.pagebased_contact',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_pagebased_domain_model_contact',
                'itemsProcFunc' => \Zeroseven\Pagebased\Backend\TCA\ItemsProcFunc::class . '->contacts',
                'default' => 0,
                'items' => [[
                    'label' => '-',
                    'value' => 0,
                    'icon' => $GLOBALS['TCA']['tx_pagebased_domain_model_contact']['ctrl']['typeicon_classes']['default'] ?? ''
                ]]
            ]
        ],
        'pagebased_relations_to' => [
            'exclude' => true,
            'label' => 'LLL:EXT:pagebased/Resources/Private/Language/locallang_db.xlf:pages.pagebased_relations_to',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'group',
                'allowed' => $table,
                'foreign_table' => $table,
                'MM_opposite_field' => 'pagebased_relations_from',
                'MM' => 'tx_pagebased_relation_mm',
                'size' => 5,
                'autoSizeMax' => 10,
                'maxitems' => 99,
                'filter' => [[
                    'userFunc' => \Zeroseven\Pagebased\Backend\TCA\GroupFilter::class . '->filterObject'
                ]],
                'suggestOptions' => [
                    'default' => [
                        'receiverClass' => \Zeroseven\Pagebased\Backend\Form\Wizard\SuggestRelationReceiver::class,
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
        'pagebased_relations_from' => [
            'exclude' => true,
            'label' => 'LLL:EXT:pagebased/Resources/Private/Language/locallang_db.xlf:pages.pagebased_relations_from',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'group',
                'foreign_table' => $table,
                'allowed' => $table,
                'size' => 5,
                'maxitems' => 100,
                'MM' => 'tx_pagebased_relation_mm',
                'readOnly' => 1
            ]
        ],
        'pagebased_redirect_category' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:pagebased/Resources/Private/Language/locallang_db.xlf:pages.pagebased_redirect_category',
            'description' => 'LLL:EXT:pagebased/Resources/Private/Language/locallang_db.xlf:pages.pagebased_redirect_category.description',
            'displayCond' => 'FIELD:uid:REQ:false', // Hide field by default
            'config' => [
                'type' => 'check',
                'items' => [[
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled',
                    'value' => 1
                ]],
                'default' => 0
            ]
        ]
    ]);

    // System relevant fields must exist in TCA to map their values to the model
    foreach ([
                 'SYS_LASTCHANGED', 'crdate',
                 \Zeroseven\Pagebased\Utility\DetectionUtility::SITE_FIELD_NAME,
                 \Zeroseven\Pagebased\Utility\DetectionUtility::REGISTRATION_FIELD_NAME,
                 \Zeroseven\Pagebased\Utility\DetectionUtility::CHILD_OBJECT_FIELD_NAME
             ] as $fieldName) {
        !isset($GLOBALS['TCA'][$table]['columns'][$fieldName])
        || \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [$fieldName => ['config' => ['type' => 'passthrough']]]);
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($table, '--div--;LLL:EXT:pagebased/Resources/Private/Language/locallang_db.xlf:pages.tab.pagebased_settings, pagebased_top, pagebased_date, pagebased_tags, pagebased_topics, pagebased_contact, pagebased_relations_to, pagebased_relations_from, pagebased_redirect_category,' . implode(',', [
            \Zeroseven\Pagebased\Utility\DetectionUtility::SITE_FIELD_NAME,
            \Zeroseven\Pagebased\Utility\DetectionUtility::REGISTRATION_FIELD_NAME,
            \Zeroseven\Pagebased\Utility\DetectionUtility::CHILD_OBJECT_FIELD_NAME
        ]), '');
}, 'pages');
