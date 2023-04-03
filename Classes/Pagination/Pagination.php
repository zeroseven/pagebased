<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Pagination;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Utility\CastUtility;

class Pagination
{
    protected array $items;
    protected Stages $stages;
    protected int $selectedStage;
    protected string $itemsPerStage;
    protected int $maxStages;
    protected array $stageLengths;

    /** @throws TypeException */
    public function __construct(mixed $items, mixed $selectedStage, mixed $itemsPerStage = null, mixed $maxStages = null)
    {
        $this->stages = GeneralUtility::makeInstance(Stages::class, $this);

        $this->setItems($items, false)
            ->setSelectedStage($selectedStage, false)
            ->setItemsPerStage(empty($itemsPerStage) ? 6 : $itemsPerStage, false)
            ->setMaxStages(empty($maxStages) ? 100 : $maxStages, false)
            ->initialize();
    }

    protected function updateStageLengths(): void
    {
        $stageLengths = GeneralUtility::intExplode(',', $this->getItemsPerStage(), true);
        $stages = array_slice($stageLengths, 0, $this->getMaxStages());

        // Set calculated lengths
        $this->stageLengths = array_replace(array_fill(0, $this->getMaxStages(), end($stages)), array_values($stages));
    }

    protected function initialize(): void
    {
        $this->updateStageLengths();
        $this->getStages()->initialize();
    }

    protected function update(): void
    {
        $this->initialize();
    }

    public function getStages()
    {
        return $this->stages;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    /** @throws TypeException */
    public function setItems(mixed $items, bool $updatePagination = null): self
    {
        $this->items = CastUtility::array($items);

        if ($updatePagination !== false) {
            $this->update();
        }

        return $this;
    }

    /** @throws TypeException */
    public function setSelectedStage(mixed $stage = null, bool $updatePagination = null): self
    {
        $this->selectedStage = CastUtility::int($stage);

        if ($updatePagination !== false) {
            $this->update();
        }

        return $this;
    }

    protected function getItemsPerStage(): string
    {
        return $this->itemsPerStage;
    }

    /** @throws TypeException */
    public function setItemsPerStage(mixed $itemsPerStage, bool $updatePagination = null): self
    {
        $this->itemsPerStage = CastUtility::string($itemsPerStage);

        if ($updatePagination !== false) {
            $this->update();
        }

        return $this;
    }

    public function getMaxStages(): int
    {
        return $this->maxStages;
    }

    /** @throws TypeException */
    public function setMaxStages(mixed $maxStages, bool $updatePagination = null): self
    {
        $this->maxStages = min(100, max(1, CastUtility::int($maxStages)));

        if ($updatePagination !== false) {
            $this->update();
        }

        return $this;
    }

    public function getStageLengths(): array
    {
        return $this->stageLengths;
    }

    public function getSelectedStage(): int
    {
        return $this->selectedStage;
    }

    public function getNextStage(): ?int
    {
        return $this->getSelectedStage() < $this->getMaxStages() - 1 && ($selectedStage = $this->stages->getSelected()) && count($this->getItems()) > $selectedStage->getRange()->getTo() ? ($this->getSelectedStage() + 1) : null;
    }

    public function getPreviousStage(): ?int
    {
        return $this->getSelectedStage() > 0 ? $this->getSelectedStage() - 1 : null;
    }

    public function getIndicators(): array
    {
        $items = [];
        $count = 0;
        $total = count($this->getItems());

        foreach ($this->getStageLengths() as $key => $value) {
            if (($count += $value) > $total) {
                return $items;
            }

            $items[$key] = $key + 1;
        }

        return $items;
    }
}
