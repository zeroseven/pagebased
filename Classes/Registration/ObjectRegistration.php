<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Domain\Model\Demand\ObjectDemand;

class ObjectRegistration extends AbstractObjectRegistration
{
    protected ?string $demandClassName = null;
    protected bool $tagField = false;
    protected bool $topField = false;

    public function getDemandClassName(): ?string
    {
        return $this->demandClassName;
    }

    public function getDemandClass(...$arguments): DemandInterface
    {
        if ($this->demandClassName) {
            return GeneralUtility::makeInstance($this->demandClassName, $this->className, $arguments);
        }

        return GeneralUtility::makeInstance(ObjectDemand::class, $this->className, $arguments);
    }

    public function setDemandClassName(string $demandClassName): self
    {
        $this->demandClassName = $demandClassName;
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


    public static function create(...$arguments): self
    {
        return GeneralUtility::makeInstance(self::class, ...$arguments);
    }
}
