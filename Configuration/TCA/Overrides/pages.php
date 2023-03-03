<?php

defined('TYPO3') || die('🤬 F**k off!');

call_user_func(static function (string $table) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [
        '_rampage_top' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages._rampage_top',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled', 1]
                ],
                'default' => 0
            ]
        ],
        '_rampage_relations_to' => [
            'exclude' => true,
            'displayCond' => 'FIELD:l10n_parent:=:0',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages._rampage_relations_to',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => $table,
                'foreign_table' => $table,
                'MM_opposite_field' => '_rampage_relations_from',
                'MM' => 'tx_rampage_relation_mm',
                'size' => 5,
                'autoSizeMax' => 10,
                'maxitems' => 99
            ]
        ],
        '_rampage_relations_from' => [
            'exclude' => true,
            'displayCond' => 'FIELD:l10n_parent:=:0',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:pages._rampage_relations_from',
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
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled', 1]
                ],
                'default' => 0
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
}, 'pages');
