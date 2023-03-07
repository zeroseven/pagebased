<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model\Demand;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Exception\ValueException;
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
    public function parseValue(mixed $value): mixed
    {
        if ($this->isArray()) {
            return array_map(static fn($v) => CastUtility::string($v), CastUtility::array($this->handleArrayModifier($value)));
        } elseif ($this->isInteger()) {
            return CastUtility::int($value);
        } elseif ($this->isBoolean()) {
            return CastUtility::bool($value);
        } elseif ($this->isString()) {
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
    protected function parseArrayModifier(mixed $value): ?array
    {
        if (is_string($value) && $this->isArray() && preg_match('/^(?:\:=\s*)?((?:addTo|removeFrom|toggleIn)List)\((.*)\)$/', trim($value), $matches)) {
            return [$matches[1], CastUtility::array($matches[2])];
        }

        return null;
    }

    /** @throws TypeException */
    protected function handleArrayModifier(mixed $value): mixed
    {
        if ($parts = $this->parseArrayModifier($value)) {
            return $this->{$parts[0]}($parts[1]);
        }

        return $value;
    }

    /** @throws TypeException */
    protected function toggleInList(array $toggleValues): array
    {
        $values = $this->getValue();

        foreach ($toggleValues as $toggleValue) {
            if (($key = array_search((string)$toggleValue, $values, true)) !== false) {
                unset($values[$key]);
            } else {
                $values[] = $toggleValue;
            }
        }

        return $values;
    }

    /** @throws TypeException */
    protected function addToList(array $newValues): array
    {
        $values = $this->getValue();

        foreach ($newValues as $newValue) {
            if (!in_array((string)$newValue, $values, true)) {
                $values[] = $newValue;
            }
        }

        return $values;
    }

    /** @throws TypeException */
    protected function removeFromList(array $removeValues): array
    {
        $values = $this->getValue();

        foreach ($removeValues as $removeValue) {
            if (($key = array_search((string)$removeValue, $values, true)) !== false) {
                unset($values[$key]);
            }
        }

        return $values;
    }

    /** @throws TypeException | ValueException */
    public function isActive(mixed $value): bool
    {
        if ($this->isArray()) {
            if (($parts = $this->parseArrayModifier($value))) {
                foreach ($parts[1] as $needle) {
                    $inArray = in_array($needle, $this->getValue(), true);

                    if (($inArray && ($parts[0] === 'toggleInList' || $parts[0] === 'addToList')) || (!$inArray && $parts[0] === 'removeFromList')) {
                        return true;
                    }
                }
            } else if (count(array_diff($this->parseValue($value), $this->getValue())) === 0) {
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
        } catch (TypeException | ValueException $e) {
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
