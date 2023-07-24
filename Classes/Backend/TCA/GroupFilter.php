<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Backend\TCA;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Pagebased\Utility\ObjectUtility;

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
                    if (MathUtility::canBeInterpretedAsInteger($value)) {
                        $recordId = (int)$value;
                    } else {
                        preg_match('/^(?:([a-z_]+)_)?(\d+)$/', $value, $matches);
                        $recordId = (int)($matches[2] ?? 0);
                    }

                    $recordId > 0
                    && $recordId !== $uid
                    && ($recordRegistration = ObjectUtility::isObject($recordId))
                    && ($recordRegistration->getIdentifier() === $registration->getIdentifier())
                    && ($newValues[] = $value);
                }

                return $newValues;
            }
        }

        return $values;
    }
}
