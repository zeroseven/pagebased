<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use Zeroseven\Rampage\Exception\TypeException;

class CastUtility
{
    /** @throws TypeException */
    protected static function throwException($value, string $expectation = null): void
    {
        throw new TypeException(sprintf('Type of "%s" can not be converted to %s.', gettype($value), $expectation ?: debug_backtrace()[1]['function']), 1659427314);
    }

    /** @throws TypeException */
    public static function int(mixed $value): int
    {
        if (is_int($value) || empty($value) || MathUtility::canBeInterpretedAsInteger($value)) {
            return (int)$value;
        }

        if ($value instanceof AbstractDomainObject) {
            return $value->getUid();
        }

        self::throwException($value);

        return 0;
    }

    /** @throws TypeException */
    public static function string(mixed $value): string
    {
        if ($value === null || is_string($value) || is_int($value)) {
            return (string)$value;
        }

        self::throwException($value);

        return '';
    }

    /** @throws TypeException */
    public static function array(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (empty($value)) {
            return [];
        }

        if (is_string($value)) {
            return GeneralUtility::trimExplode(',', $value);
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        self::throwException($value);

        return [];
    }

    /** @throws TypeException */
    public static function bool(mixed $value): bool
    {
        if (!is_array($value) && !is_object($value)) {
            return (bool)$value;
        }

        self::throwException($value);

        return false;
    }
}
