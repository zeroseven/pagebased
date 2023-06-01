<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use ReflectionClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Domain\Model\Demand\GenericObjectDemand;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Utility\CastUtility;

final class ObjectRegistration extends AbstractRegistrationEntityProperty
{
    protected ?string $controllerClassName = null;
    protected bool $tags = false;
    protected bool $top = false;
    protected array $topicPageIds = [];
    protected array $contactPageIds = [];
    protected bool $relations = false;
    protected ?string $name = null;

    public function getDemandClassName(): string
    {
        return $this->demandClassName ?? GenericObjectDemand::class;
    }

    public function getDemandClass(): DemandInterface
    {
        return ($demandClass = $this->getDemandClassName()) === GenericObjectDemand::class
            ? GenericObjectDemand::build($this->className)
            : GeneralUtility::makeInstance($demandClass);
    }

    public function getControllerClassName(): ?string
    {
        return $this->controllerClassName;
    }

    public function setControllerClass(string $controllerClassName): self
    {
        $this->controllerClassName = $controllerClassName;
        return $this;
    }

    public function enableTags(): self
    {
        $this->tags = true;
        return $this;
    }

    public function disableTags(): self
    {
        $this->tags = false;
        return $this;
    }

    public function tagsEnabled(): bool
    {
        return $this->tags;
    }

    public function enableTop(): self
    {
        $this->top = true;
        return $this;
    }

    public function disableTop(): self
    {
        $this->top = false;
        return $this;
    }

    public function topEnabled(): bool
    {
        return $this->top;
    }

    public function enableTopics(mixed $pageIds): self
    {
        $this->addTopicPageIds($pageIds);
        return $this;
    }

    public function addTopicPageIds($pageIds): self
    {
        try {
            $this->topicPageIds = array_merge($this->topicPageIds ?? [], array_map(static fn($pageId) => (int)$pageId, array_filter(CastUtility::array($pageIds), static fn($pageId) => MathUtility::canBeInterpretedAsInteger($pageId))));
        } catch (TypeException $e) {
        }

        return $this;
    }

    public function disableTopics(): self
    {
        $this->topicPageIds = [];
        return $this;
    }

    public function getTopicPageIds(): array
    {
        return $this->topicPageIds;
    }

    public function topicsEnabled(): bool
    {
        return count($this->topicPageIds) > 0;
    }

    public function enableContact(mixed $pageIds): self
    {
        $this->addContactPageIds($pageIds);
        return $this;
    }

    public function addContactPageIds($pageIds): self
    {
        try {
            $this->contactPageIds = array_merge($this->contactPageIds ?? [], array_map(static fn($pageId) => (int)$pageId, array_filter(CastUtility::array($pageIds), static fn($pageId) => MathUtility::canBeInterpretedAsInteger($pageId))));
        } catch (TypeException $e) {
        }

        return $this;
    }

    public function disableContact(): self
    {
        $this->contactPageIds = [];
        return $this;
    }

    public function getContactPageIds(): array
    {
        return $this->contactPageIds;
    }

    public function contactEnabled(): bool
    {
        return count($this->contactPageIds) > 0;
    }

    public function enableRelations(): self
    {
        $this->relations = true;
        return $this;
    }

    public function disableRelations(): self
    {
        $this->relations = false;
        return $this;
    }

    public function relationsEnabled(): bool
    {
        return $this->relations;
    }

    public function getName(): string
    {
        return $this->name ?? ($this->name = $this->name = GeneralUtility::makeInstance(ReflectionClass::class, $this->className)->getShortName());
    }

    public static function create(...$arguments): self
    {
        return GeneralUtility::makeInstance(self::class, ...$arguments);
    }
}
