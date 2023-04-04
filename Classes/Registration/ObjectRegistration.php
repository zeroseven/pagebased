<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Domain\Model\Demand\GenericObjectDemand;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Utility\CastUtility;

class ObjectRegistration extends AbstractObjectRegistration
{
    protected ?string $controllerClassName = null;
    protected bool $tagField = false;
    protected bool $topField = false;
    protected array $topicPageIds = [];

    public function getDemandClass(): DemandInterface
    {
        if ($className = $this->getDemandClassName()) {
            return GeneralUtility::makeInstance($className);
        }

        return GenericObjectDemand::build($this->className);
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
        $this->tagField = true;

        return $this;
    }

    public function disableTags(): self
    {
        $this->tagField = false;

        return $this;
    }

    public function tagsEnabled(): bool
    {
        return $this->tagField;
    }

    public function enableTop(): self
    {
        $this->topField = true;

        return $this;
    }

    public function disableTop(): self
    {
        $this->topField = false;

        return $this;
    }

    public function topEnabled(): bool
    {
        return $this->topField;
    }

    public function enableTopics(mixed $pageIds): self
    {
        try {
            $this->topicPageIds = array_map(static fn($pageId) => (int)$pageId, array_filter(CastUtility::array($pageIds), static fn($pageId) => MathUtility::canBeInterpretedAsInteger($pageId)));
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

    public static function create(...$arguments): self
    {
        return GeneralUtility::makeInstance(self::class, ...$arguments);
    }
}
