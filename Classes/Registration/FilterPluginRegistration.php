<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FilterPluginRegistration extends AbstractRegistrationPluginProperty
{
    protected string $type = 'filter';

    public static function create(...$arguments): self
    {
        return GeneralUtility::makeInstance(self::class, ...$arguments);
    }
}
