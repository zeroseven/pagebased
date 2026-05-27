<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Pagebased\Registration\Registration;

class ObjectUtility
{
    private static function classifier(): PageClassifierInterface
    {
        return GeneralUtility::makeInstance(PageClassifier::class);
    }

    public static function isSystemPage(?int $pageUid = null, ?array $row = null): bool
    {
        return self::classifier()->isSystemPage($pageUid, $row);
    }

    public static function isCategory(?int $pageUid = null, ?array $row = null): ?Registration
    {
        return self::classifier()->isCategory($pageUid, $row);
    }

    public static function isObject(?int $pageUid = null, ?array $row = null): ?Registration
    {
        return self::classifier()->isObject($pageUid, $row);
    }

    public static function isChildObject(mixed $uid): ?Registration
    {
        return self::classifier()->isChildObject($uid);
    }

    public static function findCategoryInRootLine(mixed $startPoint): ?Registration
    {
        return self::classifier()->findCategoryInRootLine($startPoint);
    }
}
