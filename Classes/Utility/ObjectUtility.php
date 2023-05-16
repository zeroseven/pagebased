<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

class ObjectUtility
{
    protected static function getPageTypeField(): string
    {
        return $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['type'];
    }

    protected static function getPageUid(): ?int
    {
        return ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController
            ? (int)$GLOBALS['TSFE']->id
            : null;
    }

    public static function isCategory(int $pageUid = null, array $row = null): ?Registration
    {
        if (($typeField = self::getPageTypeField()) && ($pageUid || ($pageUid = (int)($row['uid'] ?? 0)) || ($pageUid = self::getPageUid()))) {
            $documentType = $row[$typeField] ?? (BackendUtility::getRecord(AbstractPage::TABLE_NAME, $pageUid, $typeField)[$typeField] ?? null);

            if ($documentType && $registration = RegistrationService::getRegistrationByCategoryDocumentType((int)$documentType)) {
                return $registration;
            }
        }

        return null;
    }

    public static function isObject(int $pageUid = null, array $row = null): ?Registration
    {
        if (($typeField = self::getPageTypeField()) && ($pageUid || ($pageUid = (int)($row['uid'] ?? 0)) || ($pageUid = self::getPageUid()))) {
            $registrationField = DetectionUtility::REGISTRATION_FIELD_NAME;

            if (!isset($row[$typeField], $row[$registrationField])) {
                $row = BackendUtility::getRecord(AbstractPage::TABLE_NAME, $pageUid, implode(',', [$registrationField, $typeField]));
            }

            if (($identifier = $row[$registrationField]) && !self::isCategory($pageUid, $row) && $registration = RegistrationService::getRegistrationByIdentifier($identifier)) {
                return $registration;
            }
        }

        return null;
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
