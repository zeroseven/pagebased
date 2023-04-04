<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Domain\Model\Demand\CategoryDemand;
use Zeroseven\Rampage\Domain\Model\Demand\ObjectDemandInterface;

class CategoryRegistration extends AbstractObjectRegistration
{
    public static function create(...$arguments): self
    {
        return GeneralUtility::makeInstance(self::class, ...$arguments);
    }

    public function getDemandClass(...$arguments): ObjectDemandInterface
    {
        if ($this->demandClassName) {
            return GeneralUtility::makeInstance($this->demandClassName, $this->className, $arguments);
        }

        return GeneralUtility::makeInstance(CategoryDemand::class, $this->className, $arguments);
    }
}
