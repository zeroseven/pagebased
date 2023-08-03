<?php

return [
    'frontend' => [
        'zeroseven/pagebased/structured-data' => [
            'target' => \Zeroseven\Pagebased\Middleware\StructuredData::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ]
        ],
        'zeroseven/pagebased/category-redirect' => [
            'target' => \Zeroseven\Pagebased\Middleware\CategoryRedirect::class,
            'before' => [
                'typo3/cms-frontend/shortcut-and-mountpoint-redirect'
            ],
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ]
        ],
        'zeroseven/pagebased/rss-feed' => [
            'target' => \Zeroseven\Pagebased\Middleware\RssFeed::class,
            'before' => [
                'typo3/cms-frontend/page-resolver'
            ]
        ]
    ]
];
