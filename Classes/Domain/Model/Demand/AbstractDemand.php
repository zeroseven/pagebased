<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model\Demand;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use Zeroseven\Rampage\Exception\PropertyException;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Exception\ValueException;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\CastUtility;

abstract class AbstractDemand implements DemandInterface
{
    public const PARAMETER_UID_LIST = '_id';
    public const PARAMETER_ORDER_BY = '_sorting';
    public const PARAMETER_CONTENT_ID = '_c';

    /** @var DemandProperty[] */
    protected array $properties = [];
    protected ?DataMap $dataMap = null;
    protected ?array $tableDefinition = null;

    /** @throws ValueException | TypeException | Exception */
    public function __construct(string $className, array $parameterArray = null)
    {
        try {
            $this->dataMap = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($className);
        } catch (InvalidArgumentException $e) {
        }

        $this->initProperties();

        if ($parameterArray !== null) {
            $this->setProperties(true, $parameterArray);
        }
    }

    public function addProperty(string $name, string $type, mixed $value = null): self
    {
        $this->properties[$name] = GeneralUtility::makeInstance(DemandProperty::class, $name, $type, $value);

        return $this;
    }

    protected function initProperties(): void
    {
        // Add default properties
        foreach ([self::PARAMETER_UID_LIST => DemandProperty::TYPE_ARRAY, self::PARAMETER_ORDER_BY => DemandProperty::TYPE_STRING, self::PARAMETER_CONTENT_ID => DemandProperty::TYPE_INTEGER] as $name => $type) {
            $this->addProperty($name, $type);
        }

        // Get properties from class
        if ($this->dataMap) {
            foreach (GeneralUtility::makeInstance(ReflectionClass::class, $this->dataMap->getClassName())->getProperties() ?? [] as $reflection) {
                $name = $reflection->getName();

                // Check if the property exists in the database and the type can be handled
                if (($columnMap = $this->dataMap->getColumnMap($name)) && $type = $this->getType($reflection, $columnMap)) {
                    $this->addProperty($name, $type);
                }
            }
        }
    }

    protected function getTableDefinition(): ?array
    {
        if ($this->tableDefinition === null && $this->dataMap) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->dataMap->getTableName());

            if ($schemaManager = $queryBuilder->getSchemaManager()) {
                $this->tableDefinition = $schemaManager->listTableColumns($this->dataMap->getTableName());
            }
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
            if (in_array(($type = $reflectionType->getName()), [DemandProperty::TYPE_ARRAY, DemandProperty::TYPE_INTEGER, DemandProperty::TYPE_BOOLEAN, DemandProperty::TYPE_STRING], true)) {
                return $type;
            }

            if ($reflectionType->getName() === ObjectStorage::class) {
                return DemandProperty::TYPE_ARRAY;
            }
        }

        // Get type by column map
        if (in_array($columnMap->getTypeOfRelation(), [ColumnMap::RELATION_HAS_MANY, ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY], true)) {
            return DemandProperty::TYPE_ARRAY;
        }

        // Check table definition
        if (($tableDefinition = $this->getTableDefinition()) && ($column = $tableDefinition[$columnMap->getColumnName()] ?? null) && $type = $column->getType()) {
            if ($type->getName() === 'smallint') {
                return DemandProperty::TYPE_BOOLEAN;
            }

            if ($type->getBindingType() === 1) {
                return DemandProperty::TYPE_INTEGER;

            }
            if ($type->getBindingType() === 2) {
                return DemandProperty::TYPE_STRING;
            }
        }

        return null;
    }

    public static function makeInstance(string $className, array $parameterArray = null): self
    {
        if (($registration = RegistrationService::getRegistrationByClassName($className)) && $demandClassName = $registration->getObject()->getDemandClassName()) {
            return GeneralUtility::makeInstance($demandClassName, $className, $parameterArray);
        }

        return GeneralUtility::makeInstance(static::class, $className, $parameterArray);
    }

    public function getProperty(string $propertyName): mixed
    {
        if ($property = $this->properties[$propertyName] ?? null) {
            return $property->getValue();
        }

        return null;
    }

    /** @return DemandProperty[] */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /** @throws TypeException */
    public function getDiff(array $base, array $protectedParameters = null): array
    {
        $result = [];

        foreach ($this->properties as $property) {
            $parameter = $property->getParameter();

            if (
                ($protectedParameters && in_array($parameter, $protectedParameters, true))
                || (
                    ($property->isInteger() && CastUtility::int($base[$parameter] ?? 0) !== $property->getValue())
                    || ($property->isString() && CastUtility::string($base[$parameter] ?? 0) !== $property->getValue())
                    || ($property->isBoolean() && CastUtility::bool($base[$parameter] ?? 0) !== $property->getValue())
                    || ($property->isArray() && (count(array_diff(CastUtility::array($base[$parameter] ?? null), $property->getValue())) || count(array_diff($property->getValue(), CastUtility::array($base[$parameter] ?? null)))))
                )
            ) {
                if (!empty($property->getValue())) {
                    $result[$parameter] = $property->getValue();
                } elseif (!empty($base[$parameter])) {
                    $result[$parameter] = '';
                }
            }
        }

        return $result;
    }


    public function hasProperty(string $propertyName): bool
    {
        return in_array($propertyName, $this->properties, true);
    }

    /** @throws TypeException | PropertyException */
    public function setProperty(string $propertyName, mixed $value): self
    {
        if ($property = $this->properties[$propertyName] ?? null) {
            $property->setValue($value);
        } else {
            throw new PropertyException(sprintf('Property "%s" does not exists in %s', $propertyName, __CLASS__), 1676061710);
        }

        return $this;
    }

    /** @throws TypeException | ValueException */
    public function setProperties(bool $ignoreEmptyValues = false, ...$parameterArrays): self
    {
        // Check the types of arguments
        foreach ($parameterArrays as $parameterArray) {
            if (!is_array($parameterArray)) {
                throw new ValueException('Argument must be type array, ' . gettype($parameterArray) . 'given', 1676061794);
            }

            // Set properties
            foreach ($this->properties as $property) {
                if (isset($parameterArray[$property->getParameter()])) {
                    if ($value = $parameterArray[$property->getParameter()] ?? null) {
                        $property->setValue($value);
                    } elseif ($ignoreEmptyValues === false) {
                        $property->setValue(null);
                    }
                }
            }
        }

        return $this;
    }

    public function addToProperty(string $propertyName, mixed $value): self
    {
        // TODO
        return $this;
    }

    public function removeFromProperty(string $propertyName, mixed $value): self
    {
        // TODO
        return $this;
    }

    public function getParameterArray(bool $ignoreEmptyValues = null): array
    {
        $params = [];

        // Collect values in array
        foreach ($this->properties as $property) {
            $params[$property->getParameter()] = (string)$property;
        }

        // Return array with/without empty values
        return !$ignoreEmptyValues ? $params : array_filter($params);
    }

    public function getUidList(): array
    {
        return $this->getProperty(self::PARAMETER_UID_LIST);
    }

    /** @throws TypeException | PropertyException */
    public function setUidList(mixed $value): self
    {
        $this->setProperty(self::PARAMETER_UID_LIST, $value);

        return $this;
    }

    public function getOrderBy(): string
    {
        return $this->getProperty(self::PARAMETER_ORDER_BY);
    }

    /** @throws TypeException | PropertyException */
    public function setOrderBy(mixed $value): self
    {
        $this->setProperty(self::PARAMETER_ORDER_BY, $value);

        return $this;
    }

    public function getContentId(): int
    {
        return $this->getProperty(self::PARAMETER_CONTENT_ID);
    }

    /** @throws TypeException | PropertyException */
    public function setContentId(mixed $value): self
    {
        $this->setProperty(self::PARAMETER_CONTENT_ID, $value);

        return $this;
    }

    /** @throws TypeException | PropertyException | ValueException */
    public function __call($name, $arguments)
    {
        if (preg_match('/((?:s|g)et|is|has|addTo|removeFrom)([A-Z].*)/', $name, $matches)) {
            $action = $matches[1];
            $propertyName = lcfirst($matches[2]);

            if ($action === 'set') {
                return $this->setProperty($propertyName, ...$arguments);
            }

            if ($action === 'get') {
                return $this->getProperty($propertyName);
            }

            if ($action === 'is') {
                return $this->hasProperty($propertyName);
            }

            if ($action === 'has') {
                return $this->hasProperty($propertyName);
            }

            if ($action === 'addTo') {
                return $this->addToProperty($propertyName, ...$arguments);
            }

            if ($action === 'removeFrom') {
                return $this->removeFromProperty($propertyName, ...$arguments);
            }
        }

        throw new ValueException(sprintf('Method "%s" not found in %s', $name, __CLASS__), 1676061375);
    }
}
