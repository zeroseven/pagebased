<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Pagebased Registration Fixture',
    'description' => 'Registers a full pagebased registration (incl. list plugin) at boot, used by RegistrationBootTest.',
    'category' => 'misc',
    'version' => '1.0.0',
    'state' => 'stable',
    'author' => 'zeroseven',
    'author_email' => 'typo3@zeroseven.de',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.4.99',
            'pagebased' => '',
        ],
    ],
];
