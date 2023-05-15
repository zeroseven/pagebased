<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact',
        'label' => 'firstname',
        'label_alt' => 'lastname',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'searchFields' => 'firstname, lastname, company, email, address, city, zip, country, description, image',
        'typeicon_classes' => [
            'default' => 'actions-user'
        ]
    ],
    'palettes' => [
        'name' => [
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.palette.name',
            'showitem' => 'firstname, lastname'
        ],
        'contact' => [
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.palette.contact',
            'showitem' => 'email, phone, website'
        ],
        'address' => [
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.palette.address',
            'showitem' => 'address, --linebreak--, city, zip, country'
        ]
    ],
    'types' => [
        '1' => [
            'showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, --palette--;;name, company, expertise, image,
                --div--;LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.tab.contact, --palette--;;address, --palette--;;contact,
                --div--;LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.tab.info, description, page,
                --div--;LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.tab.social, twitter, facebook, linkedin, xing'
        ]
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
                'default' => 0
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0]
                ],
                'foreign_table' => 'tx_rampage_domain_model_contact',
                'foreign_table_where' => 'AND tx_rampage_domain_model_contact.pid=###CURRENT_PID### AND tx_rampage_domain_model_contact.sys_language_uid IN (-1,0)'
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'hidden' => [
            'exclude' => false,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled', 1]
                ]
            ]
        ],
        'firstname' => [
            'exclude' => false,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.firstname',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'lastname' => [
            'exclude' => false,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.lastname',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'company' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.company',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'expertise' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.expertise',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'email' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.email',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'file, spec, folder, telephone, page, url'
                        ]
                    ]
                ],
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'phone' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.phone',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'file, mail, spec, folder, page, url'
                        ]
                    ]
                ],
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'website' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.website',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'file, mail, spec, folder, page, telephone'
                        ]
                    ]
                ],
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'address' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.address',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'city' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.city',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'zip' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.zip',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'country' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.country',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'description' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'enableRichtext' => 1,
                'default' => ''
            ]
        ],
        'image' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.image',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'image',
                [
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                    ],
                    'overrideChildTca' => [
                        'types' => [
                            '0' => ['showitem' => '--palette--;;filePalette'],
                            \TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_IMAGE => ['showitem' => '--palette--;;filePalette']
                        ]
                    ],
                    'maxitems' => 1
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            )
        ],
        'page' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.page',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'file, spec, folder, mail, telephone'
                        ]
                    ]
                ],
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'twitter' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.twitter',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'file, page, spec, folder, mail, telephone'
                        ]
                    ]
                ],
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'facebook' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.facebook',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'file, page, spec, folder, mail, telephone'
                        ]
                    ]
                ],
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'linkedin' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.linkedin',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'file, page, spec, folder, mail, telephone'
                        ]
                    ]
                ],
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'xing' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tx_rampage_domain_model_contact.xing',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'file, page, spec, folder, mail, telephone'
                        ]
                    ]
                ],
                'eval' => 'trim',
                'default' => ''
            ]
        ]
    ]
];
