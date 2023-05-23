<?php

declare(strict_types=1);

namespace {{ cookiecutter.vendor_name|capitalize }}\{{ cookiecutter.extension_key.split('_')|map('capitalize')|join  }}\Domain\Model;

use Zeroseven\Rampage\Domain\Model\AbstractCategory;

class Category extends AbstractCategory
{
    public static function getType(): int
    {
        return 77;
    }
}
