<?php

defined('TYPO3') || die();

call_user_func(static function () {
    $object = \Zeroseven\Pagebased\Registration\ObjectRegistration::create('News')
        ->setClassName(\Zeroseven\PagebasedDemo\Domain\Model\News::class)
        ->setControllerClass(\Zeroseven\PagebasedDemo\Controller\NewsController::class)
        ->setRepositoryClass(\Zeroseven\PagebasedDemo\Domain\Repository\NewsRepository::class)
        ->setSorting('title')
        ->enableTop()
        ->enableTags();

    $category = \Zeroseven\Pagebased\Registration\CategoryRegistration::create('News-Category')
        ->setClassName(\Zeroseven\PagebasedDemo\Domain\Model\Category::class)
        ->setRepositoryClass(\Zeroseven\PagebasedDemo\Domain\Repository\CategoryRepository::class)
        ->setSorting('title')
        ->setDocumentType(137);

    $listPlugin = \Zeroseven\Pagebased\Registration\ListPluginRegistration::create('News list')
        ->setDescription('Display news objects of the current category in a list');

    \Zeroseven\Pagebased\Registration\Registration::create('pagebased_demo')
        ->setObject($object)
        ->setCategory($category)
        ->enableListPlugin($listPlugin)
        ->store();
});
