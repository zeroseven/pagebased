<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\CategoryRegistration;
use Zeroseven\Rampage\Registration\ObjectRegistration;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

class IdentifierUtility
{
    public const OBJECT_FIELD_NAME = '_rampage_object_identifier';
    public const SITE_FIELD_NAME = '_rampage_site_identifier';

    private int $uid;
    private string $table;

    public function __construct(mixed $uid, string $table)
    {
        $this->uid = MathUtility::canBeInterpretedAsInteger($uid) ? (int)$uid : 0;
        $this->table = $table;
    }

    protected function getRegistrationByCategoryPageUid(int $id, array $row = null): ?Registration
    {
        if ($id > 0 && $this->table === AbstractPage::TABLE_NAME) {
            $documentType = $row['doktype'] ?? (BackendUtility::getRecord(AbstractPage::TABLE_NAME, $id, 'doktype')['doktype'] ?? null);

            try {
                if ($documentType && $registration = RegistrationService::getRegistrationByCategoryDocumentType($documentType)) {
                    return $registration;
                }
            } catch (RegistrationException $e) {
            }
        }

        return null;
    }

    public function getCategoryRegistration(): ?CategoryRegistration
    {
        return $this->getRegistrationByCategoryPageUid($this->uid)?->getCategory();
    }

    public function getObjectRegistration(): ?ObjectRegistration
    {
        if ($this->getRegistrationByCategoryPageUid($this->uid) === null) {
            foreach (RootLineUtility::collectPagesAbove($this->uid) as $uid => $row) {
                if ($registration = $this->getRegistrationByCategoryPageUid((int)$uid, $row)) {
                    return $registration->getObject();
                }
            }
        }

        return null;
    }
}
