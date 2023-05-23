<?php

declare(strict_types=1);

namespace {{ cookiecutter.vendor_name|capitalize }}\{{ cookiecutter.extension_key.split('_')|map('capitalize')|join  }}\Domain\Model;

use Zeroseven\Rampage\Domain\Model\AbstractObject;

class {{ cookiecutter.object_name|lower|capitalize }} extends AbstractObject
{
}
