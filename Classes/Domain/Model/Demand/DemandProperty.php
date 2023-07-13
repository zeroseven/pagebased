<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Model\Demand;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Pagebased\Exception\TypeException;
use Zeroseven\Pagebased\Utility\CastUtility;

class DemandProperty
{
    public const TYPE_ARRAY = 'array';
    public const TYPE_INTEGER = 'int';
    public const TYPE_BOOLEAN = 'bool';
    public const TYPE_STRING = 'string';

    protected string $name;
    protected string $type;
    protected string $parameter;
    protected string $extbasePropertyName;
    protected mixed $value;

    /** @throws TypeException */
    public function __construct(string $name, string $type, mixed $value = null, string $extbasePropertyName = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->parameter = GeneralUtility::camelCaseToLowerCaseUnderscored($name);
        $this->extbasePropertyName = $extbasePropertyName ?? $name;

        $this->setValue($value);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getParameter(): string
    {
        return $this->parameter;
    }

    public function getExtbasePropertyName(): ?string
    {
        return $this->extbasePropertyName;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function isArray(): bool
    {
        return $this->type === self::TYPE_ARRAY;
    }

    public function isInteger(): bool
    {
        return $this->type === self::TYPE_INTEGER;
    }

    public function isString(): bool
    {
        return $this->type === self::TYPE_STRING;
    }

    public function isBoolean(): bool
    {
        return $this->type === self::TYPE_BOOLEAN;
    }

    /** @throws TypeException */
    public function parseValue(mixed $value): mixed
    {
        if ($this->isArray()) {
            return array_map(static fn($v) => CastUtility::string($v), CastUtility::array($value));
        }

        if ($this->isInteger()) {
            return CastUtility::int($value);
        }

        if ($this->isBoolean()) {
            return CastUtility::bool($value);
        }

        if ($this->isString()) {
            return CastUtility::string($value);
        }

        return null;
    }

    /** @throws TypeException */
    public function setValue(mixed $value): void
    {
        $this->value = $this->parseValue($value);
    }

    /** @throws TypeException */
    public function toggleValue(mixed $value): void
    {
        if ($this->isArray()) {
            $this->isActive($value) ? $this->removeFromList($value) : $this->addToList($value);
        } else {
            $this->isActive($value) ? $this->clear() : $this->setValue($value);
        }
    }

    /** @throws TypeException */
    protected function addToList(mixed $newValues): void
    {
        foreach (CastUtility::array($newValues) as $newValue) {
            if (!in_array((string)$newValue, $this->value, true)) {
                $this->value[] = CastUtility::string($newValue);
            }
        }
    }

    /** @throws TypeException */
    protected function removeFromList(mixed $removeValues): void
    {
        foreach (CastUtility::array($removeValues) as $removeValue) {
            if (($key = array_search((string)$removeValue, $this->value, true)) !== false) {
                unset($this->value[$key]);
            }
        }
    }

    /** @throws TypeException */
    public function isActive(mixed $value): bool
    {
        if ($this->isArray()) {
            if (count(array_diff($this->parseValue($value), $this->getValue())) === 0) {
                return true;
            }
        } else if ($this->parseValue($value) === $this->getValue()) {
            return true;
        }

        return false;
    }

    public function clear(): void
    {
        try {
            $this->setValue(null);
        } catch (TypeException $e) {
        }
    }

    public function toString(): string
    {
        if ($this->isArray()) {
            $values = $this->getValue();
            sort($values);

            return implode(',', $values);
        }

        if ($this->isBoolean()) {
            return (string)(bool)$this->getValue();
        }

        try {
            return CastUtility::string($this->getValue());
        } catch (TypeException $e) {
            return '';
        }
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
