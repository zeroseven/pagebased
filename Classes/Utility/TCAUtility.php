<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Registration\Registration;

class TCAUtility
{
    protected static function getPageTypeField(): string
    {
        return $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['type'];
    }

    public static function addDisplayCondition(string $table, string $field, $condition): void
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

    public static function getObjectDisplayCondition(Registration $registration): array
    {
        return ['AND' => [
            sprintf('FIELD:%s:=:%s', DetectionUtility::REGISTRATION_FIELD_NAME, $registration->getIdentifier()),
            sprintf('FIELD:%s:!=:%d', self::getPageTypeField(), $registration->getCategory()->getObjectType())
        ]];
    }

    public static function addObjectDisplayCondition(Registration $registration, string $fieldName): void
    {
        self::addDisplayCondition(AbstractPage::TABLE_NAME, $fieldName, self::getObjectDisplayCondition($registration));
    }
}
