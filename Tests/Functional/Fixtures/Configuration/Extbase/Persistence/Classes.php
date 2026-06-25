<?php

declare(strict_types=1);

use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestCategory;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestObject;

return [
    TestObject::class => [
        'tableName' => AbstractPage::TABLE_NAME,
    ],
    TestCategory::class => [
        'tableName' => AbstractPage::TABLE_NAME,
    ],
];
