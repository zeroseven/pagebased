<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Exception\ValueException;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

class ObjectUtility
{
    protected static function getObjectCache(int $uid): ?Registration
    {
        return $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/rampage']['cache']['object'][$uid] ?? null;
    }

    protected static function setObjectCache(int $uid, ?Registration $registration = null): ?Registration
    {
        return $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/rampage']['cache']['object'][$uid] = $registration;
    }

    protected static function getPageTypeField(): string
    {
        return $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['type'];
    }

    public static function isCategory(int $pageUid = null, array $row = null): ?Registration
    {
        if ($pageUid || ($pageUid = (int)($row['uid'] ?? RootLineUtility::getCurrentPage()))) {
            $typeField = self::getPageTypeField();

            $documentType = $row[$typeField] ?? (BackendUtility::getRecord(AbstractPage::TABLE_NAME, $pageUid, $typeField)[$typeField] ?? null);

            if ($documentType && $registration = RegistrationService::getRegistrationByCategoryDocumentType((int)$documentType)) {
                return $registration;
            }
        }

        return null;
    }

    public static function isObject(int $pageUid = null, array $row = null): ?Registration
    {
        $pageUid || ($pageUid = (int)($row['uid'] ?? RootLineUtility::getCurrentPage()));

        if ($registration = self::getObjectCache($pageUid)) {
            return $registration;
        }

        if ($pageUid) {
            $typeField = self::getPageTypeField();
            $registrationField = DetectionUtility::REGISTRATION_FIELD_NAME;

            if (!isset($row[$typeField], $row[$registrationField])) {
                $row = BackendUtility::getRecord(AbstractPage::TABLE_NAME, $pageUid, implode(',', [$registrationField, $typeField]));
            }

            try {
                if (($identifier = $row[$registrationField] ?? null) && !self::isCategory($pageUid, $row) && $registration = RegistrationService::getRegistrationByIdentifier($identifier)) {
                    return self::setObjectCache($pageUid, $registration);
                }
            } catch (ValueException $e) {
            }
        }

        return self::setObjectCache($pageUid);
    }

    public static function isChildObject(mixed $uid): ?Registration
    {
        try {
            if ($parentPages = RootLineUtility::collectPagesAbove(CastUtility::int($uid), false, 1)) {
                foreach ($parentPages as $parentPage) {
                    if ($registration = self::isObject(null, $parentPage)) {
                        return $registration;
                    }
                }
            }
        } catch (TypeException $e) {
        }

        return null;
    }

    public static function findRegistrationInRootLine(mixed $startPoint): ?Registration
    {
        if (MathUtility::canBeInterpretedAsInteger($startPoint)) {
            foreach (RootLineUtility::collectPagesAbove($startPoint, true) as $uid => $row) {
                if ($registration = self::isCategory((int)$uid, $row)) {
                    return $registration;
                }

                if ($registration = self::isObject((int)$uid, $row)) {
                    return $registration;
                }
            }
        }

        return null;
    }

    public static function findObjectInRootLine(mixed $startPoint): ?Registration
    {
        if (MathUtility::canBeInterpretedAsInteger($startPoint)) {
            foreach (RootLineUtility::collectPagesAbove($startPoint, true) as $uid => $row) {
                if ($registration = self::isObject((int)$uid, $row)) {
                    return $registration;
                }
            }
        }

        return null;
    }

    public static function findCategoryInRootLine(mixed $startPoint): ?Registration
    {
        if (MathUtility::canBeInterpretedAsInteger($startPoint)) {
            foreach (RootLineUtility::collectPagesAbove($startPoint, true) as $uid => $row) {
                if ($registration = self::isCategory((int)$uid, $row)) {
                    return $registration;
                }
            }
        }

        return null;
    }
}
