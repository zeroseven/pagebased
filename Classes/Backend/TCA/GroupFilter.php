<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Backend\TCA;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Utility\ObjectUtility;

class GroupFilter
{
    public function filterObject(array $parameters, mixed $parent): array
    {
        $table = $parameters['tcaFieldConfig']['foreign_table'] ?? '';
        $values = $parameters['values'] ?? null;

        if ($parent instanceof DataHandler) {
            $uid = (int)array_key_first($parent->datamap[$table] ?? []);
            $registration = ObjectUtility::isObject($uid);

            if ($registration && $values) {
                $newValues = [];

                foreach ($values as $value) {
                    preg_match('/^(?:([a-z_]+)_)?(\d+)$/', $value, $matches)
                    && ($recordUid = (int)($matches[2] ?? 0)) && $recordUid !== $uid
                    && ($matches[1] === AbstractPage::TABLE_NAME)
                    && ($recordRegistration = ObjectUtility::isObject($recordUid))
                    && ($recordRegistration->getIdentifier() === $registration->getIdentifier())
                    && ($newValues[] = $value);
                }

                return $newValues;
            }
        }

        return $values;
    }
}
