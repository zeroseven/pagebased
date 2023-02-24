<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Pagination;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Stage extends ObjectStorage
{
    protected Pagination $pagination;
    protected int $index;
    protected bool $active;
    protected bool $selected;

    public function __construct(Pagination $pagination, $index = null, $active = null, $selected = null)
    {
        $this->pagination = $pagination;
        $this->index = $index ?? 0;
        $this->active = $active ?? false;
        $this->selected = $selected ?? false;
    }

    public function getIndex(): int
    {
        return (int)$this->index;
    }

    public function setIndex(int $index): self
    {
        $this->index = $index;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public function setSelected(bool $selected): self
    {
        $this->selected = $selected;
        return $this;
    }

    public function getItems(): array
    {
        return $this->toArray();
    }

    public function getRange(): Range
    {
        return GeneralUtility::makeInstance(Range::class, $this->pagination, $this);
    }
}
