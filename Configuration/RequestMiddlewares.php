<?php

return [
    'frontend' => [
        'zeroseven/rampage/structured_data' => [
            'target' => \Zeroseven\Rampage\Middleware\StructuredData::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ]
        ]
    ]
];
