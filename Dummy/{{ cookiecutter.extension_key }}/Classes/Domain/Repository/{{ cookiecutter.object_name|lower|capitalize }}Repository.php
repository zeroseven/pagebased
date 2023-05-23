<?php

declare(strict_types=1);

namespace {{ cookiecutter.vendor_name|capitalize }}\{{ cookiecutter.extension_key.split('_')|map('capitalize')|join  }}\Domain\Repository;

use Zeroseven\Rampage\Domain\Repository\AbstractObjectRepository;

class {{ cookiecutter.object_name|lower|capitalize }}Repository extends AbstractObjectRepository
{
}
