<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class CategoryRegistration extends AbstractObjectRegistration
{
    public static function create(...$arguments): self
    {
        return GeneralUtility::makeInstance(self::class, ...$arguments);
    }
}
