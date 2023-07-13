 <?php

defined('TYPO3') || die('🧨');

call_user_func(static function () {
    $object = \Zeroseven\Pagebased\Registration\ObjectRegistration::create('{{ cookiecutter.object_name|capitalize }}')
        ->setClassName(\{{ cookiecutter.__namespace_vendor }}\{{ cookiecutter.__namespace_extension }}\Domain\Model\{{ cookiecutter.__object_class_name }}::class)
        ->setControllerClass(\{{ cookiecutter.__namespace_vendor }}\{{ cookiecutter.__namespace_extension }}\Controller\{{ cookiecutter.__object_class_name }}Controller::class)
        ->setRepositoryClass(\{{ cookiecutter.__namespace_vendor }}\{{ cookiecutter.__namespace_extension }}\Domain\Repository\{{ cookiecutter.__object_class_name }}Repository::class)
        ->enableTopics(1)
        ->enableContact(1)
        ->enableRelations()
        ->enableTop()
        ->enableTags();

    $category = \Zeroseven\Pagebased\Registration\CategoryRegistration::create('{{ cookiecutter.object_name|capitalize }}-Category')
        ->setClassName(\{{ cookiecutter.__namespace_vendor }}\{{ cookiecutter.__namespace_extension }}\Domain\Model\Category::class)
        ->setRepositoryClass(\{{ cookiecutter.__namespace_vendor }}\{{ cookiecutter.__namespace_extension }}\Domain\Repository\CategoryRepository::class)
        ->setDocumentType({{ cookiecutter.category_doktype }});

    $listPlugin = \Zeroseven\Pagebased\Registration\ListPluginRegistration::create('{{ cookiecutter.object_name|capitalize }} list')
        ->setDescription('Display object in a list')
        ->setIconIdentifier('content-bullets');

    $filterPlugin = \Zeroseven\Pagebased\Registration\FilterPluginRegistration::create('{{ cookiecutter.object_name|capitalize }} filter')
        ->setDescription('Filter objects');

    \Zeroseven\Pagebased\Registration\Registration::create('{{ cookiecutter.extension_key }}')
        ->setObject($object)
        ->setCategory($category)
        ->enableListPlugin($listPlugin)
        ->enableFilterPlugin($filterPlugin)
        ->store();
});
