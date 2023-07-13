<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ListPluginRegistration extends AbstractRegistrationPluginProperty
{
    protected string $type = 'list';

    public static function create(...$arguments): self
    {
        return GeneralUtility::makeInstance(self::class, ...$arguments);
    }
}
