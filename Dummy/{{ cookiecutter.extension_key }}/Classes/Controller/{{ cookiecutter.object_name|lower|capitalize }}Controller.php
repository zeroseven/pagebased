<?php

declare(strict_types=1);

namespace {{ cookiecutter.vendor_name|capitalize }}\{{ cookiecutter.extension_key.split('_')|map('capitalize')|join  }}\Controller;

use Zeroseven\Rampage\Controller\AbstractPageObjectController;

class {{ cookiecutter.object_name|lower|capitalize }}Controller extends AbstractPageObjectController
{
}
