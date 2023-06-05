<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;

interface ObjectControllerInterface extends ControllerInterface
{
    public function listAction(): void;
}
