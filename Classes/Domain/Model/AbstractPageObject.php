<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Annotation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

abstract class AbstractPageObject extends AbstractPage implements PageObjectInterface
{
    protected bool $top;
    protected ?AbstractPage $parentObject;
    protected ?QueryResultInterface $childObjects;
    protected ?AbstractPageCategory $category;
    protected ?ObjectStorage $relations;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Zeroseven\Rampage\Domain\Model\AbstractPage>
     * Annotation\Cascade("remove")
     * Annotation\Lazy
     */
    protected ObjectStorage $relationsTo;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Zeroseven\Rampage\Domain\Model\AbstractPage>
     * Annotation\Cascade("remove")
     * Annotation\Lazy
     */
    protected ObjectStorage $relationsFrom;

    protected function initStorageObjects(): void
    {
        parent::initStorageObjects();

        $this->relations = new ObjectStorage();
        $this->relationsTo = new ObjectStorage();
        $this->relationsFrom = new ObjectStorage();
    }

    public function isTop(): bool
    {
        return $this->top;
    }

    public function setTop(bool $value): self
    {
        $this->top = $value;

        return $this;
    }

    public function getParentObject(): ?AbstractPage
    {
        // Todo: find parent object

        return $this->parentObject;
    }

    public function getChildObjects(): QueryResultInterface
    {
        // Todo: find child objects

        return $this->childObjects;
    }

    public function getCategory(): ?AbstractPageCategory
    {
        // Todo: Find category
        return $this->category;
    }

    public function setCategory(AbstractPageCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getRelationsTo(): ObjectStorage
    {
        return $this->relationsTo;

    }

    public function setRelationsTo(ObjectStorage $relationsTo): self
    {
        $this->relationsTo = $relationsTo;
        $this->relations = null;

        return $this;
    }

    public function getRelationsFrom(): ObjectStorage
    {
        return $this->relationsFrom;
    }

    public function setRelationsFrom(ObjectStorage $relationsFrom): self
    {
        $this->relationsFrom = $relationsFrom;
        $this->relations = null;

        return $this;
    }

    public function getRelations(): ObjectStorage
    {
        if ($this->relations === null) {
            $this->relations = GeneralUtility::makeInstance(ObjectStorage::class);

            if ($relationsTo = $this->getRelationsTo()) {
                $this->relations->addAll($relationsTo);
            }

            if ($relationsFrom = $this->getRelationsFrom()) {
                $this->relations->addAll($relationsFrom);
            }
        }

        return $this->relations;
    }
}
