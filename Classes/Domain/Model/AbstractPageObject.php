<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Annotation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Rampage\Domain\Model\Entity\PageObject;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Utility\CastUtility;

abstract class AbstractPageObject extends AbstractPage implements PageObjectInterface
{
    protected const TAG_DELIMITER = ',';

    protected bool $top;
    protected string $tagsString;
    protected array $tags = [];
    protected ?ObjectStorage $topics = null;
    protected ?PageObject $parentObject = null;
    protected ?QueryResultInterface $childObjects = null;
    protected ?AbstractPageCategory $category = null;
    protected ?ObjectStorage $relations = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Zeroseven\Rampage\Domain\Model\Entity\PageObject>
     * Annotation\Cascade("remove")
     * Annotation\Lazy
     */
    protected ObjectStorage $relationsTo;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Zeroseven\Rampage\Domain\Model\Entity\PageObject>
     * Annotation\Cascade("remove")
     * Annotation\Lazy
     */
    protected ObjectStorage $relationsFrom;

    protected function initStorageObjects(): void
    {
        parent::initStorageObjects();

        $this->topics = new ObjectStorage();
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

    public function getTags(): array
    {
        if ($tagsString = $this->tagsString ?? null) {
            return $this->tags = GeneralUtility::trimExplode(self::TAG_DELIMITER, $tagsString, true);
        }

        return $this->tags;
    }

    /** @throws TypeException */
    public function setTags(mixed $value): self
    {
        $this->tags = CastUtility::array($value, self::TAG_DELIMITER);
        $this->tagsString = implode(self::TAG_DELIMITER, $this->tags);

        return $this;
    }

    public function addTopic(Topic $topic): void
    {
        $this->topics->attach($topic);
    }

    public function removeTopic(Topic $topicToRemove): void
    {
        $this->topics->detach($topicToRemove);
    }

    public function getTopics(): ObjectStorage
    {
        return $this->topics;
    }

    public function setTopics(ObjectStorage $topics): self
    {
        $this->topics = $topics;
        return $this;
    }

    public function getParentObject(): ?PageObject
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
