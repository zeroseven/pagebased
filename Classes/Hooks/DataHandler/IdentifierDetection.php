<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Hooks\DataHandler;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\RootLineUtility;
use Zeroseven\Rampage\Utility\SettingsUtility;

class IdentifierDetection
{
    protected function updateIdentifier(string $table, mixed $id, array &$fieldArray): void
    {
        if ($table === AbstractPage::TABLE_NAME && MathUtility::canBeInterpretedAsInteger($id)) {
            $isCategory = RegistrationService::getRegistrationByCategoryPageUid((int)$id, $fieldArray) !== null;
            $registration = RegistrationService::getObjectRegistrationInRootLine((int)$id);

            $fieldArray[SettingsUtility::SITE_FIELD_NAME] = ($isCategory || $registration) ? RootLineUtility::getRootPage((int)$id) : 0;
            $fieldArray[SettingsUtility::REGISTRATION_FIELD_NAME] = $registration ? $registration->getIdentifier() : '';
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
