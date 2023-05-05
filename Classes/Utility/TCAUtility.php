<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

class TCAUtility
{
    public static function addDisplayCondition(string $table, string $field, string $condition): void
    {
        if (isset($GLOBALS['TCA'][$table]['columns'][$field]['displayCond']['OR'])) {
            $GLOBALS['TCA'][$table]['columns'][$field]['displayCond']['OR'][] = $condition;

            return;
        }

        if ($existingCondition = $GLOBALS['TCA'][$table]['columns'][$field]['displayCond'] ?? null) {
            $GLOBALS['TCA'][$table]['columns'][$field]['displayCond'] = ['OR' => [$existingCondition, $condition]];

            return;
        }

        $GLOBALS['TCA'][$table]['columns'][$field]['displayCond'] = $condition;
    }
}
