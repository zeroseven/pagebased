<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Pagebased',
    'description' => 'The ultimate tool to create page based extensions',
    'category' => 'plugin',
    'author' => 'Raphael Thanner',
    'author_email' => 'r.thanner@zeroseven.de',
    'author_company' => 'zeroseven design studios GmbH',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '3.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.4.99'
        ],
        'suggests' => [
            'pagebased_blog' => ''
        ]
    ]
];
