<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Pagebased Demo',
    'description' => 'Dev-only demo consumer extension. Registers a "News" object/category type so the pagebased dev instance has something to classify.',
    'category' => 'plugin',
    'author' => 'zeroseven design studios GmbH',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.4.99',
            'pagebased' => '',
        ],
    ],
];
