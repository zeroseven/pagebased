<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;

interface PageObjectControllerInterface extends ControllerInterface
{
    public function initializeRegistration(): void;

    public function initializeDemand(): void;

    public function listAction(): void;
}
