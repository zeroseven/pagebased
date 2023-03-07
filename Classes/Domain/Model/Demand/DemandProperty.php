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

    /** @throws TypeException | ValueException */
    public function setValue(mixed $value): void
    {
        if ($this->isArray()) {
            if (is_string($value) && preg_match('/^(?:\:=\s*)?(addTo|removeFrom)List\((.*)\)$/', trim($value), $matches)) {
                $value = $matches[2];
                $matches[1] === 'addTo' ? $this->addToList($value) : $this->removeFromList($value);
            } else {
                $this->value = array_map(static fn($v) => CastUtility::string($v), CastUtility::array($value));
            }
        } elseif ($this->isInteger()) {
            $this->value = CastUtility::int($value);
        } elseif ($this->isBoolean()) {
            $this->value = CastUtility::bool($value);
        } elseif ($this->isString()) {
            $this->value = CastUtility::string($value);
        }
    }

    /** @throws TypeException | ValueException */
    public function addToList(mixed $newValues): void
    {
        if ($this->isArray()) {
            $values = $this->getValue();
            foreach (CastUtility::array($newValues) as $newValue) {
                if (!in_array((string)$newValue, $values, true)) {
                    $values[] = $newValue;
                }
            }

            $this->value = $values;
        } else {
            throw new ValueException(sprintf('Method "addToList" is only available for type "%s".', self::TYPE_ARRAY), 1678136702);
        }
    }

    /** @throws TypeException | ValueException */
    public function removeFromList(mixed $removeValues): void
    {
        if ($this->isArray()) {
            $values = $this->getValue();

            foreach (CastUtility::array($removeValues) as $removeValue) {
                if (($key = array_search((string)$removeValue, $values, true)) !== false) {
                    unset($values[$key]);
                }
            }

            $this->value = $values;
        } else {
            throw new ValueException(sprintf('Method "removeFromList" is only available for type "%s".', self::TYPE_ARRAY), 1678136702);
        }
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
