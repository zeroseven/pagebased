<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Backend\Identifier;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Utility\RootLineUtility;

class IdentifierDataHandlerHook
{
    protected function updateIdentifier(string $table, mixed $id, array &$fieldArray): void
    {
        $detector = GeneralUtility::makeInstance(IdentifierDetector::class, $id, $table);
        $categoryRegistration = $detector->getCategoryRegistration();
        $objectRegistration = $detector->getObjectRegistration();

        $fieldArray[IdentifierDetector::SITE_FIELD_NAME] = ($categoryRegistration || $objectRegistration) ? RootLineUtility::getRootPage((int)$id) : 0;
        $fieldArray[IdentifierDetector::OBJECT_FIELD_NAME] = $objectRegistration ? $objectRegistration->getClassName() : '';
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
