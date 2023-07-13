<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Pagination;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Stages extends ObjectStorage
{
    protected Pagination $pagination;

    public function __construct(Pagination $pagination)
    {
        $this->pagination = $pagination;
    }

    public function initialize(): void
    {
        // Remove all existing objects
        $this->removeAll($this);

        // Create array of items
        $items = $this->pagination->getItems();

        // Build new stages
        foreach ($this->pagination->getStageLengths() as $index => $stageLength) {
            if (count($items)) {

                // Calculate states
                $active = $index <= $this->pagination->getSelectedStage();
                $selected = $index === $this->pagination->getSelectedStage();

                // Create stage object
                $stage = GeneralUtility::makeInstance(Stage::class, $this->pagination, $index, $active, $selected);

                // Add items to stage
                foreach (array_splice($items, 0, $stageLength ?: null) as $item) {
                    $stage->attach($item);
                }

                // Add stage to the stages
                $this->attach($stage);
            }
        }
    }

    public function getFirst(): ?Stage
    {
        return $this->offsetGet(0);
    }

    public function getSelected(): ?Stage
    {
        if ($this->offsetExists($this->pagination->getSelectedStage())) {
            return $this->offsetGet($this->pagination->getSelectedStage());
        }
        return null;
    }

    public function getCurrent(): ?Stage
    {
        return $this->getSelected();
    }

    public function getNext(): ?Stage
    {
        $index = $this->pagination->getSelectedStage() + 1;

        if ($this->offsetExists($index)) {
            return $this->offsetGet($index);
        }

        return null;
    }

    public function getActive(): array
    {
        return array_filter($this->toArray(), static function ($stage) {
            return $stage->isActive();
        });
    }

    public function getInactive(): array
    {
        return array_filter($this->toArray(), static function ($stage) {
            return !$stage->isActive();
        });
    }
}
