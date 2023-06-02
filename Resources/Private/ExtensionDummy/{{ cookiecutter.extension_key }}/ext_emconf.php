<?php

$EM_CONF[$_EXTKEY] = [
    'title' => '{{ cookiecutter.__object_class_name }}',
    'category' => 'plugin',
//  'author' => 'Max Mustermann',
//  'author_email' => 'm.mustermann@zeroseven.de',
    'author_company' => 'zeroseven design studios GmbH',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '0.0.0',
    'constraints' => [
        'typo3' => '11.5.0-11.5.99',
        'rampage' => ''
    ]
];
