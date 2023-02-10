<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model\Demand;

use ReflectionClass;
use ReflectionProperty;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

abstract class AbstractDemand
{
    protected array $parameter = [];
    protected array $types = [];
    protected DataMap $dataMap;
    protected ?array $tableDefinition = null;

    /** @throws Exception */
    public function __construct(string $className)
    {
        $this->dataMap = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($className);

        foreach (GeneralUtility::makeInstance(ReflectionClass::class, $className)->getProperties() ?? [] as $reflection) {
            $name = $reflection->getName();

            // Check if the property exists in the database and the type can be handled
            if (($columnMap = $this->dataMap->getColumnMap($name)) && $type = $this->parseType($reflection, $columnMap)) {
                $this->parameter[$name] = GeneralUtility::camelCaseToLowerCaseUnderscored($name);
                $this->types[$name] = $type;
            }
        }
    }

    protected function getTableDefinition(): ?array
    {
        if ($this->tableDefinition === null) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->dataMap->getTableName());
            $this->tableDefinition = $queryBuilder->getSchemaManager()->listTableColumns($this->dataMap->getTableName());
        }

        return $this->tableDefinition;
    }

    protected function parseType(ReflectionProperty $reflection, ColumnMap $columnMap): ?string
    {

        // The field must not be defined in table controls
        if ($ctrl = $GLOBALS['TCA'][$this->dataMap->getTableName()]['ctrl']) {
            $fieldName = $columnMap->getColumnName();

            if (
                ($ctrl['descriptionColumn'] ?? null) === $fieldName ||
                ($ctrl['enableColumns']['disabled'] ?? null) === $fieldName ||
                ($ctrl['enableColumns']['fe_group'] ?? null) === $fieldName ||
                ($ctrl['enableColumns']['endtime'] ?? null) === $fieldName ||
                ($ctrl['enableColumns']['starttime'] ?? null) === $fieldName ||
                ($ctrl['languageField'] ?? null) === $fieldName ||
                ($ctrl['origUid'] ?? null) === $fieldName ||
                ($ctrl['translationSource'] ?? null) === $fieldName ||
                ($ctrl['transOrigDiffSourceField'] ?? null) === $fieldName ||
                ($ctrl['transOrigPointerField'] ?? null) === $fieldName ||
                ($ctrl['type'] ?? null) === $fieldName
            ) {
                return null;
            }
        }

        // Get type by class reflection
        if ($reflectionType = $reflection->getType()) {
            if (in_array(($type = $reflectionType->getName()), ['int', 'bool', 'array', 'string'])) {
                return $type;
            }

            if ($reflectionType->getName() === ObjectStorage::class) {
                return 'array';
            }
        }

        // Get type by column map
        if (in_array($columnMap->getTypeOfRelation(), [ColumnMap::RELATION_HAS_MANY, ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY], true)) {
            return 'array';
        }

        // Check table definition
        if (($tableDefinition = $this->getTableDefinition()) && ($column = $tableDefinition[$columnMap->getColumnName()] ?? null) && $type = $column->getType()) {
            if ($type->getName() === 'smallint') {
                return 'bool';
            }

            if ($type->getBindingType() === 1) {
                return 'int';

            }
            if ($type->getBindingType() === 2) {
                return 'string';
            }
        }

        return null;
    }

    public static function makeInstance(string $className): self
    {
        return GeneralUtility::makeInstance(static::class, $className);
    }
}
