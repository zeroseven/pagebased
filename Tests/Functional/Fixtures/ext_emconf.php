<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Pagebased Test Fixtures',
    'description' => 'Test fixtures for pagebased functional tests',
    'category' => 'misc',
    'version' => '1.0.0',
    'state' => 'stable',
    'author' => 'zeroseven',
    'author_email' => 'typo3@zeroseven.de',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.4.99',
        ],
    ],
];
