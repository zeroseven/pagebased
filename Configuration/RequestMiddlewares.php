<?php

declare(strict_types=1);

use Zeroseven\Pagebased\Middleware\CategoryRedirect;
use Zeroseven\Pagebased\Middleware\RssFeed;
use Zeroseven\Pagebased\Middleware\StructuredData;

return [
    'frontend' => [
        'zeroseven/pagebased/structured-data' => [
            'target' => StructuredData::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'zeroseven/pagebased/category-redirect' => [
            'target' => CategoryRedirect::class,
            'before' => [
                'typo3/cms-frontend/shortcut-and-mountpoint-redirect',
            ],
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'zeroseven/pagebased/rss-feed' => [
            'target' => RssFeed::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
        ],
    ],
];
