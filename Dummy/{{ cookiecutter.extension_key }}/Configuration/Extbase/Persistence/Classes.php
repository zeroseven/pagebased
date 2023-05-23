<?php

return \Zeroseven\Rampage\Registration\RegistrationService::extbasePersistenceConfiguration([
    \{{ cookiecutter.vendor_name|capitalize }}\{{ cookiecutter.extension_key.split('_')|map('capitalize')|join  }}\Domain\Model\{{ cookiecutter.object_name|lower|capitalize }}::class => [],
    \{{ cookiecutter.vendor_name|capitalize }}\{{ cookiecutter.extension_key.split('_')|map('capitalize')|join  }}\Domain\Model\Category::class => []
]);
