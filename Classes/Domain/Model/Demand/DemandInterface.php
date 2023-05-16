<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model\Demand;

interface DemandInterface
{
    public const PARAMETER_UID_LIST = '_id';
    public const PARAMETER_ORDER_BY = '_sorting';

    public function addProperty(string $name, string $type, string $extbasePropertyName = null): self;

    public function getProperty(string $propertyName): DemandProperty;

    public function getProperties(): array;

    public function hasProperty(string $propertyName): bool;

    public function setProperty(string $propertyName, mixed $value, bool $toggle = null): self;

    public function toggleProperty(string $propertyName, mixed $value): self;

    public function setProperties(array $propertyArray, bool $ignoreEmptyValues = null, bool $toggle = null): self;

    public function toggleProperties(array $propertyArray, bool $ignoreEmptyValues = null): self;

    public function setParameterArray(array $parameterArray, bool $ignoreEmptyValues = null): self;

    public function getParameterArray(bool $ignoreEmptyValues = null): array;

    public function getParameterDiff(array $base, array $protectedParameters = null): array;

    public function clear(): self;

    public function getUidList(): array;

    public function setUidList(mixed $value): self;

    public function getOrderBy(): string;

    public function setOrderBy(mixed $value): self;

    public function getCopy(): self;
}
