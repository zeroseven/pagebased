<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Backend\TCA;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\IdentifierUtility;

class GroupFilter
{
    protected function getObjectIdentifier(int $uid): ?string
    {
        $row = BackendUtility::getRecord(AbstractPage::TABLE_NAME, $uid, IdentifierUtility::OBJECT_FIELD_NAME);

        return $row[IdentifierUtility::OBJECT_FIELD_NAME] ?? null;
    }

    /** @throws RegistrationException */
    public function filterObject(array $parameters, DataHandler $dataHandler): array
    {
        $table = $parameters['tcaFieldConfig']['foreign_table'] ?? '';
        $values = $parameters['values'] ?? null;

        $uid = (int)array_key_first($dataHandler->datamap[$table] ?? []);
        $objectIdentifier = $this->getObjectIdentifier((int)$uid);
        $registration = RegistrationService::getRegistrationByClassName($objectIdentifier);

        if ($objectIdentifier && $registration && $values) {
            $matches = [];

            foreach ($values as $value) {
                if (preg_match('/^(?:([a-z_]+)_)?(\d+)$/', $value, $matches)
                    && ($recordUid = (int)($matches[2] ?? 0)) && $recordUid !== $uid
                    && $matches[1] && $matches[1] === AbstractPage::TABLE_NAME
                    && $this->getObjectIdentifier($recordUid) === $objectIdentifier
                ) {
                    $matches[] = $value;
                }
            }

            return $matches;
        }

        return $values;
    }
}
