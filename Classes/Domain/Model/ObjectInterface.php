<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

interface ObjectInterface extends PageEntityInterface
{
    public function isTop(): bool;

    public function setTop(bool $value): self;

    public function getTags(): array;

    public function setTags(mixed $value): self;

    public function getParentObject(): ?ObjectInterface;

    public function getChildObjects(): ?QueryResultInterface;

    public function getCategory(): ?AbstractCategory;

    public function getRelationsTo(): ObjectStorage;

    public function setRelationsTo(ObjectStorage $objectStorage): self;

    public function getRelationsFrom(): ObjectStorage;

    public function setRelationsFrom(ObjectStorage $objectStorage): self;

    public function getRelations(): ObjectStorage;
}
