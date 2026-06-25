<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Model;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Pagebased\Domain\Model\Entity\PageObject;
use Zeroseven\Pagebased\Exception\TypeException;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;
use Zeroseven\Pagebased\Utility\CastUtility;
use Zeroseven\Pagebased\Utility\ObjectUtility;
use Zeroseven\Pagebased\Utility\RootLineUtility;

abstract class AbstractObject extends AbstractPage implements ObjectInterface
{
    protected const TAG_DELIMITER = ',';

    protected bool $top;

    protected \DateTime $date;

    protected string $tagsString;

    protected array $tags = [];

    protected ?Contact $contact = null;

    protected ?ObjectStorage $relations = null;

    protected bool $childObject;

    /**
     * @var ObjectStorage<Topic>|null
     */
    #[Lazy]
    protected ?ObjectStorage $topics = null;

    #[Lazy]
    protected ?ObjectInterface $linkedObject = null;

    #[Lazy]
    protected ?ObjectInterface $parentObject = null;

    #[Lazy]
    protected ?QueryResultInterface $childObjects = null;

    #[Lazy]
    protected ?AbstractCategory $category = null;

    /**
     * @var ObjectStorage<PageObject>
     * Annotation\Cascade("remove")
     */
    #[Lazy]
    protected ObjectStorage $relationsTo;

    /**
     * @var ObjectStorage<PageObject>
     * Annotation\Cascade("remove")
     */
    #[Lazy]
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

    public function getDate(): ?\DateTime
    {
        return $this->date ?? null;
    }

    public function setDate(\DateTime $date): self
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
        if (!$this->relations instanceof ObjectStorage) {
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

    public function isChildObject(): bool
    {
        return $this->childObject;
    }

    public function addTopic(Topic $topic): void
    {
        $this->topics->attach($topic);
    }

    public function removeTopic(Topic $topic): void
    {
        $this->topics->detach($topic);
    }

    public function getTopics(): ?ObjectStorage
    {
        return $this->topics;
    }

    public function setTopics(ObjectStorage $objectStorage): self
    {
        $this->topics = $objectStorage;
        return $this;
    }

    public function getCategory(): ?AbstractCategory
    {
        if (!$this->category instanceof AbstractCategory) {
            foreach (RootLineUtility::collectPagesAbove($this->uid) as $row) {
                if (($registration = ObjectUtility::isCategory(null, $row)) instanceof Registration) {
                    return $this->category = $registration->getCategory()->getRepositoryClass()->findByUid($row['uid']);
                }
            }
        }

        return $this->category;
    }

    public function getLinkedObject(): ?ObjectInterface
    {
        if (
            !$this->linkedObject instanceof ObjectInterface
            && $this->shortcut > 0
            && $this->shortcutMode === 0
            && $this->getDocumentType() === PageRepository::DOKTYPE_SHORTCUT
            && ($registration = RegistrationService::getRegistrationByObjectClass($this))
            && ($linkedObject = $registration->getObject()->getRepositoryClass()->findByUid($this->shortcut))
        ) {
            return $this->linkedObject = $linkedObject;
        }

        return $this->linkedObject;
    }

    public function getParentObject(): ?ObjectInterface
    {
        if (
            !$this->parentObject instanceof ObjectInterface
            && $this->isChildObject()
            && count($parentPages = RootLineUtility::collectPagesAbove($this->uid, false, 1))
            && ($registration = RegistrationService::getRegistrationByObjectClass($this))
        ) {
            return $this->parentObject = $registration->getObject()->getRepositoryClass()->findByUid(array_key_first($parentPages));
        }

        return $this->parentObject;
    }

    public function getChildObjects(): ?QueryResultInterface
    {
        if (!$this->childObjects instanceof QueryResultInterface && $registration = RegistrationService::getRegistrationByObjectClass($this)) {
            return $this->childObjects = $registration->getObject()->getRepositoryClass()->findChildObjects($this);
        }

        return $this->childObjects;
    }

    public function getRelationsTo(): ObjectStorage
    {
        $objectStorage = GeneralUtility::makeInstance(ObjectStorage::class);

        foreach ($this->relationsTo as $relationTo) {
            if ($relationTo->getLanguageUid() === $this->getLanguageUid()) {
                $objectStorage->attach($relationTo);
            }
        }

        return $objectStorage;
    }

    public function setRelationsTo(ObjectStorage $objectStorage): self
    {
        $this->relationsTo = $objectStorage;
        $this->relations = null;

        return $this;
    }

    public function getRelationsFrom(): ObjectStorage
    {
        $objectStorage = GeneralUtility::makeInstance(ObjectStorage::class);

        foreach ($this->relationsFrom as $relationFrom) {
            if ($relationFrom->getLanguageUid() === $this->getLanguageUid()) {
                $objectStorage->attach($relationFrom);
            }
        }

        return $objectStorage;
    }

    public function setRelationsFrom(ObjectStorage $objectStorage): self
    {
        $this->relationsFrom = $objectStorage;
        $this->relations = null;

        return $this;
    }
}
