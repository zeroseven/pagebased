<?php

return [
    'frontend' => [
        'zeroseven/rampage/structured-data' => [
            'target' => \Zeroseven\Rampage\Middleware\StructuredData::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ]
        ],
        'zeroseven/rampage/category-redirect' => [
            'target' => \Zeroseven\Rampage\Middleware\CategoryRedirect::class,
            'before' => [
                'typo3/cms-frontend/shortcut-and-mountpoint-redirect'
            ],
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ]
        ]
    ]
];
