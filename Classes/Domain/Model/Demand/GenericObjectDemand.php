<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Model\Demand;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class GenericObjectDemand extends AbstractObjectDemand
{
    public static function build(string $objectClassName): DemandInterface
    {
        return GeneralUtility::makeInstance(self::class)->detectPropertiesFromClass($objectClassName);
    }
}
