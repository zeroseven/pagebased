 <?php

defined('TYPO3') || die('🧨');

call_user_func(static function () {
    $object = \Zeroseven\Rampage\Registration\ObjectRegistration::create('{{ cookiecutter.object_name|capitalize }}')
        ->setClassName(\{{ cookiecutter.vendor_name|capitalize }}\{{ cookiecutter.extension_key.split('_')|map('capitalize')|join  }}\Domain\Model\{{ cookiecutter.object_name|lower|capitalize }}::class)
        ->setControllerClass(\{{ cookiecutter.vendor_name|capitalize }}\{{ cookiecutter.extension_key.split('_')|map('capitalize')|join  }}\Controller\{{ cookiecutter.object_name|lower|capitalize }}Controller::class)
        ->setRepositoryClass(\{{ cookiecutter.vendor_name|capitalize }}\{{ cookiecutter.extension_key.split('_')|map('capitalize')|join  }}\Domain\Repository\{{ cookiecutter.object_name|lower|capitalize }}Repository::class)
        ->enableTopics(25)
        ->enableTop()
        ->enableTags();

    $category = \Zeroseven\Rampage\Registration\CategoryRegistration::create('{{ cookiecutter.object_name|capitalize }}-Category')
        ->setClassName(\{{ cookiecutter.vendor_name|capitalize }}\{{ cookiecutter.extension_key.split('-')|map('capitalize')|join  }}\Domain\Model\Category::class)
        ->setRepositoryClass(\{{ cookiecutter.vendor_name|capitalize }}\{{ cookiecutter.extension_key.split('-')|map('capitalize')|join  }}\Domain\Repository\CategoryRepository::class);

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
});
