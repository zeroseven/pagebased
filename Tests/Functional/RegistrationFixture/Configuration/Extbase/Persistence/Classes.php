<?php

use Zeroseven\Pagebased\Registration\RegistrationService;
use Zeroseven\Pagebased\Tests\Functional\RegistrationFixture\Classes\FixtureCategory;
use Zeroseven\Pagebased\Tests\Functional\RegistrationFixture\Classes\FixtureObject;

return RegistrationService::extbasePersistenceConfiguration([
    FixtureObject::class => [],
    FixtureCategory::class => [],
]);
