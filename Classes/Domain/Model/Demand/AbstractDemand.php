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
    public const PARAMETER_ID_LIST = '_id';
    public const PARAMETER_ORDER_BY = '_sorting';

    public const TYPE_ARRAY = 'array';
    public const TYPE_INTEGER = 'int';
    public const TYPE_BOOLEAN = 'bool';
    public const TYPE_STRING = 'string';

    protected array $parameter = [];
    protected array $types = [];
    protected DataMap $dataMap;
    protected ?array $tableDefinition = null;

    /** @throws Exception */
    public function __construct(string $className)
    {
        $this->dataMap = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($className);
        $this->initProperties();
    }

    public function addProperty(string $name, string $type): void
    {
        $this->parameter[$name] = GeneralUtility::camelCaseToLowerCaseUnderscored($name);
        $this->types[$name] = $type;
    }

    protected function initProperties(): void
    {
        // Add default properties
        foreach ([self::PARAMETER_ID_LIST => self::TYPE_ARRAY, self::PARAMETER_ORDER_BY => self::TYPE_STRING] as $name => $type) {
            $this->addProperty($name, $type);
        }

        // Get properties from class
        foreach (GeneralUtility::makeInstance(ReflectionClass::class, $this->dataMap->getClassName())->getProperties() ?? [] as $reflection) {
            $name = $reflection->getName();

            // Check if the property exists in the database and the type can be handled
            if (($columnMap = $this->dataMap->getColumnMap($name)) && $type = $this->getType($reflection, $columnMap)) {
                $this->addProperty($name, $type);
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

    protected function getType(ReflectionProperty $reflection, ColumnMap $columnMap): ?string
    {

        // The field must not be defined in table controls
        if ($ctrl = $GLOBALS['TCA'][$this->dataMap->getTableName()]['ctrl']) {
            $fieldName = $columnMap->getColumnName();

            if (
                'uid' === $fieldName ||
                'pid' === $fieldName ||
                ($ctrl['cruser_id'] ?? null) === $fieldName ||
                ($ctrl['descriptionColumn'] ?? null) === $fieldName ||
                ($ctrl['editlock'] ?? null) === $fieldName ||
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
            if (in_array(($type = $reflectionType->getName()), [self::TYPE_ARRAY, self::TYPE_INTEGER, self::TYPE_BOOLEAN, self::TYPE_STRING])) {
                return $type;
            }

            if ($reflectionType->getName() === ObjectStorage::class) {
                return self::TYPE_ARRAY;
            }
        }

        // Get type by column map
        if (in_array($columnMap->getTypeOfRelation(), [ColumnMap::RELATION_HAS_MANY, ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY], true)) {
            return self::TYPE_ARRAY;
        }

        // Check table definition
        if (($tableDefinition = $this->getTableDefinition()) && ($column = $tableDefinition[$columnMap->getColumnName()] ?? null) && $type = $column->getType()) {
            if ($type->getName() === 'smallint') {
                return self::TYPE_BOOLEAN;
            }

            if ($type->getBindingType() === 1) {
                return self::TYPE_INTEGER;

            }
            if ($type->getBindingType() === 2) {
                return self::TYPE_STRING;
            }
        }

        return null;
    }

    public static function makeInstance(string $className): self
    {
        return GeneralUtility::makeInstance(static::class, $className);
    }
}
