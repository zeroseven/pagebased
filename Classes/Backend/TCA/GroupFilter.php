<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Backend\TCA;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GroupFilter
{
    public function filterTypes(array $parameters): array
    {
        $table = $parameters['tcaFieldConfig']['foreign_table'] ?? '';
        $type = $GLOBALS['TCA'][$table]['ctrl']['type'] ?? null;
        $values = $parameters['values'] ?? null;
        $allowed = $parameters['allowed'] ?? null;

        if ($type && $values && $allowed) {
            $matches = [];
            $allowedTypes = GeneralUtility::trimExplode(',', $allowed, true);

            foreach ($values as $value) {
                if (preg_match('/^([a-z_]+)_(\d+)$/', $value, $matches)
                    && ($table === $matches[1])
                    && ($row = BackendUtility::getRecord($matches[1], (string)$matches[2], $type))
                    && in_array((string)$row[$type], $allowedTypes, true)) {
                    $matches[] = $value;
                }
            }

            return $matches;
        }

        return [];
    }
}
