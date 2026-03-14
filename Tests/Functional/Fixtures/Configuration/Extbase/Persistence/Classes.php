<?php

declare(strict_types=1);

return [
    \Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestObject::class => [
        'tableName' => \Zeroseven\Pagebased\Domain\Model\AbstractPage::TABLE_NAME,
    ],
    \Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestCategory::class => [
        'tableName' => \Zeroseven\Pagebased\Domain\Model\AbstractPage::TABLE_NAME,
    ],
];
