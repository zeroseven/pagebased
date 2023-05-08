<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Backend\TCA;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Utility\SettingsUtility;

class GroupFilter
{
    protected function getRegistrationIdentifier(int $uid): ?string
    {
        $row = BackendUtility::getRecord(AbstractPage::TABLE_NAME, $uid, SettingsUtility::REGISTRATION_FIELD_NAME);

        return $row[SettingsUtility::REGISTRATION_FIELD_NAME] ?? null;
    }

    public function filterObject(array $parameters, DataHandler $dataHandler): array
    {
        $table = $parameters['tcaFieldConfig']['foreign_table'] ?? '';
        $values = $parameters['values'] ?? null;

        $uid = (int)array_key_first($dataHandler->datamap[$table] ?? []);
        $registrationIdentifier = $this->getRegistrationIdentifier($uid);

        if ($registrationIdentifier && $values) {
            $matches = [];

            foreach ($values as $value) {
                if (preg_match('/^(?:([a-z_]+)_)?(\d+)$/', $value, $matches)
                    && ($recordUid = (int)($matches[2] ?? 0)) && $recordUid !== $uid
                    && $matches[1] && $matches[1] === AbstractPage::TABLE_NAME
                    && $this->getRegistrationIdentifier($recordUid) === $registrationIdentifier
                ) {
                    $matches[] = $value;
                }
            }

            return $matches;
        }

        return $values;
    }
}
