<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractDemand;
use Zeroseven\Rampage\Domain\Model\PageTypeInterface;

interface PageTypeControllerInterface extends ControllerInterface
{
    public function getDemand(bool $applySettings = null, bool $applyRequestArguments = null, ...$arguments): AbstractDemand;

    public function listAction(): void;

    public function getObject(): PageTypeInterface;
}
