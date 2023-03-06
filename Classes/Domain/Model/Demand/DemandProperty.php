<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model\Demand;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Utility\CastUtility;

class DemandProperty
{
    public const TYPE_ARRAY = 'array';
    public const TYPE_INTEGER = 'int';
    public const TYPE_BOOLEAN = 'bool';
    public const TYPE_STRING = 'string';

    protected string $name;
    protected string $type;
    protected string $parameter;
    protected mixed $value;

    /** @throws TypeException */
    public function __construct(string $name, string $type, mixed $value = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->parameter = GeneralUtility::camelCaseToLowerCaseUnderscored($name);

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
    public function setValue(mixed $value): void
    {
        if ($this->isArray()) {
            $this->value = CastUtility::array($value);
        } elseif ($this->isInteger()) {
            $this->value = CastUtility::int($value);
        } elseif ($this->isBoolean()) {
            $this->value = CastUtility::bool($value);
        } elseif ($this->isString()) {
            $this->value = CastUtility::string($value);
        }
    }

    /** @throws TypeException */
    public function clear(): void
    {
        $this->setValue(null);
    }

    public function __toString(): string
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
}
