<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Registration\Registration;

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
            sprintf('FIELD:%s:!=:%d', self::getPageTypeField(), $registration->getCategory()->getDocumentType())
        ]];
    }

    public static function getCategoryDisplayCondition(Registration $registration): string
    {
        return sprintf('FIELD:%s:=:%d', self::getPageTypeField(), $registration->getCategory()->getDocumentType());
    }

    public static function addObjectDisplayCondition(Registration $registration, string $fieldName): void
    {
        self::addDisplayCondition(AbstractPage::TABLE_NAME, $fieldName, self::getObjectDisplayCondition($registration));
    }

    public static function addCategoryDisplayCondition(Registration $registration, string $fieldName): void
    {
        self::addDisplayCondition(AbstractPage::TABLE_NAME, $fieldName, self::getCategoryDisplayCondition($registration));
    }
}
