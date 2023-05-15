<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Rampage\Domain\Model\Entity\PageObject;

interface PageObjectInterface extends PageEntityInterface
{
    public function isTop(): bool;

    public function setTop(bool $value): self;

    public function getTags(): array;

    public function setTags(mixed $value): self;

    public function getParentObject(): ?PageObject;

    public function getChildObjects(): ?QueryResultInterface;

    public function getCategory(): ?AbstractPageCategory;

    public function getRelationsTo(): ObjectStorage;

    public function setRelationsTo(ObjectStorage $relationsTo): self;

    public function getRelationsFrom(): ObjectStorage;

    public function setRelationsFrom(ObjectStorage $relationsFrom): self;

    public function getRelations(): ObjectStorage;
}
