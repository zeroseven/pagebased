<?php

declare(strict_types=1);

use Zeroseven\Pagebased\Registration\CategoryRegistration;
use Zeroseven\Pagebased\Registration\ListPluginRegistration;
use Zeroseven\Pagebased\Registration\ObjectRegistration;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Tests\Functional\RegistrationFixture\Classes\FixtureCategory;
use Zeroseven\Pagebased\Tests\Functional\RegistrationFixture\Classes\FixtureCategoryRepository;
use Zeroseven\Pagebased\Tests\Functional\RegistrationFixture\Classes\FixtureObject;
use Zeroseven\Pagebased\Tests\Functional\RegistrationFixture\Classes\FixtureObjectController;
use Zeroseven\Pagebased\Tests\Functional\RegistrationFixture\Classes\FixtureObjectRepository;

defined('TYPO3') || die();

// A full registration WITH a list plugin, stored at boot. Exercises the whole cross-version
// registration pipeline that RegistrationBootTest asserts: icon registration (BootCompletedEvent),
// page/user TSConfig events, plugin/CType registration (configurePlugin), the FlexForm data
// structure registration (v13 ds-pointer vs v14 columnsOverrides) and registration validation.
call_user_func(static function (): void {
    $object = ObjectRegistration::create('Smoke')
        ->setClassName(FixtureObject::class)
        ->setControllerClass(FixtureObjectController::class)
        ->setRepositoryClass(FixtureObjectRepository::class)
        ->enableTop()
        ->enableTags();

    $category = CategoryRegistration::create('Smoke-Category')
        ->setClassName(FixtureCategory::class)
        ->setRepositoryClass(FixtureCategoryRepository::class)
        ->setDocumentType(188);

    $listPlugin = ListPluginRegistration::create('Smoke list')
        ->setDescription('List plugin used by RegistrationBootTest');

    Registration::create('RegistrationFixture')
        ->setObject($object)
        ->setCategory($category)
        ->enableListPlugin($listPlugin)
        ->store();
});
