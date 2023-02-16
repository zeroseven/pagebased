<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface;

interface PageTypeControllerInterface extends ControllerInterface
{
    public function listAction(): void;
}
