<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Hooks\DataHandler;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\ObjectRegistration;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\RootLineUtility;

class Identifier
{
    protected const OBJECT_FIELD_NAME = '_rampage_object_identifier';
    protected const SITE_FIELD_NAME = '_rampage_site_identifier';

    protected function getCategoryRegistration(int $id, ?array $row): ?Registration
    {
        $documentType = $row['doktype'] ?? (BackendUtility::getRecord(AbstractPage::TABLE_NAME, $id, 'doktype')['doktype'] ?? null);

        try {
            if ($documentType && $registration = RegistrationService::getRegistrationByCategoryDocumentType($documentType)) {
                return $registration;
            }
        } catch (RegistrationException $e) {
        }

        return null;
    }

    protected function getObjectRegistration(int $id): ?ObjectRegistration
    {
        foreach (RootLineUtility::collectPagesAbove($id) as $uid => $page) {
            if ($registration = $this->getCategoryRegistration((int)$uid, $page)) {
                return $registration->getObject();
            }
        }

        return null;
    }

    protected function updateIdentifier(string $table, mixed $id, array &$fieldArray): void
    {
        if ($table === AbstractPage::TABLE_NAME && MathUtility::canBeInterpretedAsInteger($id)) {
            $isCategory = $this->getCategoryRegistration((int)$id, $fieldArray) !== null;
            $objectRegistration = $isCategory ? null : $this->getObjectRegistration((int)$id);

            $fieldArray[self::SITE_FIELD_NAME] = ($isCategory || $objectRegistration) ? RootLineUtility::getRootPage((int)$id) : 0;
            $fieldArray[self::OBJECT_FIELD_NAME] = $objectRegistration ? $objectRegistration->getClassName() : '';
        }
    }

    public function processDatamap_postProcessFieldArray(string $status, string $table, mixed $id, array &$fieldArray, DataHandler $dataHandler): void
    {
        $this->updateIdentifier($table, $id, $fieldArray);
    }

    public function moveRecord_afterAnotherElementPostProcess(string $table, int $uid, int $destPid, $origDestPid, $moveRec, $updateFields, DataHandler $dataHandler): void
    {
        $this->updateIdentifier($table, $uid, $updateFields);
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = self::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][] = self::class;
    }
}
