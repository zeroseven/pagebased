<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model\Demand;

interface DemandInterface
{
    public function addProperty(string $name, string $type, mixed $value = null): self;

    public function getProperty(string $propertyName): mixed;

    public function getProperties(): array;

    public function hasProperty(string $propertyName): bool;

    public function setProperty(string $propertyName, mixed $value): self;

    public function setProperties(bool $ignoreEmptyValues = false, ...$arguments): self;

    public function addToProperty(string $propertyName, mixed $value): self;

    public function removeFromProperty(string $propertyName, mixed $value): self;

    public function getParameterArray(bool $ignoreEmptyValues = null): array;

    public function getUidList(): array;

    public function getOrderBy(): string;

    public function getContentId(): int;
}
