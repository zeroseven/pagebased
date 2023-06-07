 <?php

defined('TYPO3') || die('🧨');

call_user_func(static function () {
    // Build registration
    $object = \Zeroseven\Rampage\Registration\ObjectRegistration::create('{{ cookiecutter.object_name|capitalize }}')
        ->setClassName(\{{ cookiecutter.__namespace_vendor }}\{{ cookiecutter.__namespace_extension }}\Domain\Model\{{ cookiecutter.__object_class_name }}::class)
        ->setControllerClass(\{{ cookiecutter.__namespace_vendor }}\{{ cookiecutter.__namespace_extension }}\Controller\{{ cookiecutter.__object_class_name }}Controller::class)
        ->setRepositoryClass(\{{ cookiecutter.__namespace_vendor }}\{{ cookiecutter.__namespace_extension }}\Domain\Repository\{{ cookiecutter.__object_class_name }}Repository::class)
        ->enableTopics(25)
        ->enableTop()
        ->enableTags();

    $category = \Zeroseven\Rampage\Registration\CategoryRegistration::create('{{ cookiecutter.object_name|capitalize }}-Category')
        ->setClassName(\{{ cookiecutter.__namespace_vendor }}\{{ cookiecutter.__namespace_extension }}\Domain\Model\Category::class)
        ->setRepositoryClass(\{{ cookiecutter.__namespace_vendor }}\{{ cookiecutter.__namespace_extension }}\Domain\Repository\CategoryRepository::class)
        ->setIconIdentifier('apps-pagetree-page-{{ cookiecutter.object_name|lower }}');

    $listPlugin = \Zeroseven\Rampage\Registration\ListPluginRegistration::create('{{ cookiecutter.object_name|capitalize }} list')
        ->setDescription('Display object in a list')
        ->setIconIdentifier('content-bullets');

    $filterPlugin = \Zeroseven\Rampage\Registration\FilterPluginRegistration::create('{{ cookiecutter.object_name|capitalize }} filter')
        ->setDescription('Filter objects');

    \Zeroseven\Rampage\Registration\Registration::create('{{ cookiecutter.extension_key }}')
        ->setObject($object)
        ->setCategory($category)
        ->enableListPlugin($listPlugin)
        ->enableFilterPlugin($filterPlugin)
        ->store();

    // Add custom icons
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'apps-pagetree-page-{{ cookiecutter.object_name|lower }}',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:{{ cookiecutter.extension_key }}/Resources/Public/Icons/Apps/apps-pagetree-page-{{ cookiecutter.object_name|lower }}.svg']
    );
    $iconRegistry->registerIcon(
        'apps-pagetree-page-{{ cookiecutter.object_name|lower }}-hideinmenu',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:{{ cookiecutter.extension_key }}/Resources/Public/Icons/Apps/apps-pagetree-page-{{ cookiecutter.object_name|lower }}-hideinmenu.svg']
    );
});
