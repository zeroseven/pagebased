<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model;

use DateTime;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Rampage\Domain\Model\Entity\PageObject;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\CastUtility;
use Zeroseven\Rampage\Utility\ObjectUtility;
use Zeroseven\Rampage\Utility\RootLineUtility;

abstract class AbstractPageObject extends AbstractPage implements PageObjectInterface
{
    protected const TAG_DELIMITER = ',';

    protected bool $top;
    protected DateTime $date;
    protected string $tagsString;
    protected array $tags = [];
    protected ?Contact $contact = null;
    protected ?ObjectStorage $relations = null;

    /**
     * @var ObjectStorage<Topic> | null
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected ?ObjectStorage $topics = null;

    /**
     * @var PageObjectInterface | null
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected ?PageObjectInterface $parentObject = null;

    /**
     * @var QueryResultInterface | null
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected ?QueryResultInterface $childObjects = null;

    /**
     * @var AbstractPageCategory | null
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected ?AbstractPageCategory $category = null;

    /**
     * @var ObjectStorage<PageObject>
     * Annotation\Cascade("remove")
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected ObjectStorage $relationsTo;

    /**
     * @var ObjectStorage<PageObject>
     * Annotation\Cascade("remove")
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
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

    public function getDate(): ?DateTime
    {
        return $this->date ?? null;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;

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

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(Contact $contact): self
    {
        $this->contact = $contact;
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

    public function addTopic(Topic $topic): void
    {
        $this->topics->attach($topic);
    }

    public function removeTopic(Topic $topicToRemove): void
    {
        $this->topics->detach($topicToRemove);
    }

    public function getTopics(): ?ObjectStorage
    {
        return $this->topics;
    }

    public function setTopics(ObjectStorage $topics): self
    {
        $this->topics = $topics;
        return $this;
    }

    public function getCategory(): ?AbstractPageCategory
    {
        if ($this->category === null) {
            foreach (RootLineUtility::collectPagesAbove($this->uid) as $row) {
                if ($registration = ObjectUtility::isCategory(null, $row)) {
                    return $this->category = $registration->getCategory()->getRepositoryClass()->findByUid($row['uid']);
                }
            }
        }

        return $this->category;
    }

    public function getParentObject(): ?PageObjectInterface
    {
        if (
            $this->parentObject === null
            && count($parentPages = RootLineUtility::collectPagesAbove($this->uid, false, 1))
            && ($registration = RegistrationService::getRegistrationByClassName(get_class($this)))
        ) {
            return $this->parentObject = $registration->getObject()->getRepositoryClass()->findByUid(array_key_first($parentPages));
        }

        return null;
    }

    public function getChildObjects(): ?QueryResultInterface
    {
        if ($this->childObjects === null && $registration = RegistrationService::getRegistrationByClassName(get_class($this))) {
            return $this->childObjects = $registration->getObject()->getRepositoryClass()->findChildObjects($this);
        }

        return null;
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
}
