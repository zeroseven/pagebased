<?php

$EM_CONF[$_EXTKEY] = [
    'title' => '{{ cookiecutter.__object_class_name }}',
    'category' => 'plugin',
//  'author' => 'Max Mustermann',
//  'author_email' => 'm.mustermann@zeroseven.de',
//  'author_company' => 'Company name',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '0.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
            'pagebased' => ''
        ]
    ]
];
