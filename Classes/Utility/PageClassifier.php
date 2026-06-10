<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Exception\TypeException;
use Zeroseven\Pagebased\Exception\ValueException;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;

class PageClassifier implements PageClassifierInterface, SingletonInterface
{
    /** @var array<int, Registration|null> */
    private array $cache = [];

    protected function getPageTypeField(): string
    {
        return $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['type'];
    }

    protected function getDocumentType(?int $pageUid = null, ?array $row = null): int
    {
        $typeField = $this->getPageTypeField();

        if ($documentType = $row[$typeField] ?? null) {
            return (int)$documentType;
        }

        if ($pageUid || ($pageUid = (int)($row['uid'] ?? RootLineUtility::getCurrentPage()))) {
            $row = BackendUtility::getRecord(AbstractPage::TABLE_NAME, $pageUid, $typeField);

            return (int)($row[$typeField] ?? 0);
        }

        return 0;
    }

    public function isSystemPage(?int $pageUid = null, ?array $row = null): bool
    {
        return ($documentType = $this->getDocumentType($pageUid, $row)) && in_array($documentType, [
            PageRepository::DOKTYPE_BE_USER_SECTION,
            PageRepository::DOKTYPE_MOUNTPOINT,
            PageRepository::DOKTYPE_SPACER,
            PageRepository::DOKTYPE_SYSFOLDER,
        ], true);
    }

    public function isCategory(?int $pageUid = null, ?array $row = null): ?Registration
    {
        if (($documentType = $this->getDocumentType($pageUid, $row)) && !$this->isSystemPage($pageUid, $row)) {
            return RegistrationService::getRegistrationByCategoryDocumentType($documentType);
        }

        return null;
    }

    public function isObject(?int $pageUid = null, ?array $row = null): ?Registration
    {
        $pageUid = $pageUid ?: (int)($row['uid'] ?? RootLineUtility::getCurrentPage());

        if (array_key_exists($pageUid, $this->cache)) {
            return $this->cache[$pageUid];
        }

        if ($pageUid) {
            $typeField = $this->getPageTypeField();
            $registrationField = DetectionUtility::REGISTRATION_FIELD_NAME;

            if (!isset($row[$typeField], $row[$registrationField])) {
                $row = BackendUtility::getRecord(AbstractPage::TABLE_NAME, $pageUid, implode(',', [$registrationField, $typeField]));
            }

            try {
                if (
                    ($identifier = $row[$registrationField] ?? null)
                    && !$this->isSystemPage($pageUid, $row)
                    && !$this->isCategory($pageUid, $row)
                ) {
                    return $this->cache[$pageUid] = RegistrationService::getRegistrationByIdentifier($identifier);
                }
            } catch (ValueException $e) {
            }
        }

        return $this->cache[$pageUid] = null;
    }

    public function isChildObject(mixed $uid): ?Registration
    {
        try {
            if (!$this->isSystemPage($uid) && $parentPages = RootLineUtility::collectPagesAbove(CastUtility::int($uid), false, 1)) {
                foreach ($parentPages as $parentPage) {
                    if ($registration = $this->isObject(null, $parentPage)) {
                        return $registration;
                    }
                }
            }
        } catch (TypeException $e) {
        }

        return null;
    }

    public function findCategoryInRootLine(mixed $startPoint): ?Registration
    {
        if (MathUtility::canBeInterpretedAsInteger($startPoint)) {
            foreach (RootLineUtility::collectPagesAbove($startPoint, true) as $uid => $row) {
                if ($registration = $this->isCategory((int)$uid, $row)) {
                    return $registration;
                }
            }
        }

        return null;
    }
}
