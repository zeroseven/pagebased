<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Unit\Pagination;

use stdClass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Zeroseven\Pagebased\Pagination\Pagination;
use Zeroseven\Pagebased\Pagination\Stage;

/**
 * Tests for Pagination class.
 *
 * These tests verify the core pagination logic: stage calculation, item distribution,
 * indicator generation and next/previous stage navigation.
 * This is critical to verify before optimising the "load all items into memory" pattern.
 *
 * NOTE: Stage/Stages extend ObjectStorage which requires actual objects. All item
 * arrays therefore contain stdClass instances, not scalars.
 */
class PaginationTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /** Creates N distinct stdClass objects for use as pagination items. */
    private function makeItems(int $count): array
    {
        return array_map(static fn() => new \stdClass(), range(1, $count));
    }

    // ---------------------------------------------------------------------------
    // Items
    // ---------------------------------------------------------------------------

    /** @test */
    public function itemsAreStoredCorrectly(): void
    {
        $items = $this->makeItems(3);
        $pagination = new Pagination($items, 0);

        self::assertSame($items, $pagination->getItems());
    }

    /** @test */
    public function emptyItemsResultInEmptyArray(): void
    {
        $pagination = new Pagination([], 0);

        self::assertSame([], $pagination->getItems());
    }

    // ---------------------------------------------------------------------------
    // Stage selection
    // ---------------------------------------------------------------------------

    /** @test */
    public function selectedStageDefaultsToZero(): void
    {
        $pagination = new Pagination($this->makeItems(2), 0);

        self::assertSame(0, $pagination->getSelectedStage());
    }

    /** @test */
    public function selectedStageIsStoredCorrectly(): void
    {
        $pagination = new Pagination($this->makeItems(20), 2, 6);

        self::assertSame(2, $pagination->getSelectedStage());
    }

    // ---------------------------------------------------------------------------
    // itemsPerStage
    // ---------------------------------------------------------------------------

    /** @test */
    public function uniformItemsPerStageDistributesEvenly(): void
    {
        $pagination = new Pagination($this->makeItems(12), 0, 4);
        $stages = $pagination->getStages()->toArray();

        self::assertCount(3, $stages);
        self::assertCount(4, $stages[0]->toArray());
        self::assertCount(4, $stages[1]->toArray());
        self::assertCount(4, $stages[2]->toArray());
    }

    /** @test */
    public function commaSeparatedItemsPerStageUsesProgressiveLengths(): void
    {
        // First stage: 3 items, second: 6, then 12 for remaining stages
        $pagination = new Pagination($this->makeItems(30), 0, '3,6,12');
        $stages = $pagination->getStages()->toArray();

        self::assertCount(4, $stages); // 3 + 6 + 12 + 9 remaining (≤12) = 4 stages
        self::assertCount(3, $stages[0]->toArray());
        self::assertCount(6, $stages[1]->toArray());
        self::assertCount(12, $stages[2]->toArray());
        self::assertCount(9, $stages[3]->toArray());
    }

    /** @test */
    public function lastValueInCommaSeparatedListFillsRemainingStages(): void
    {
        $pagination = new Pagination($this->makeItems(10), 0, '4,3');
        $stageLengths = $pagination->getStageLengths();

        // From the second position on, all entries should be 3
        self::assertSame(3, $stageLengths[1]);
        self::assertSame(3, $stageLengths[2]);
        self::assertSame(3, $stageLengths[10]);
    }

    // ---------------------------------------------------------------------------
    // maxStages
    // ---------------------------------------------------------------------------

    /** @test */
    public function maxStagesIsCappedAt100(): void
    {
        $pagination = new Pagination([], 0, 6, 200);

        self::assertSame(100, $pagination->getMaxStages());
    }

    /** @test */
    public function maxStagesMinimumIsOne(): void
    {
        // Constructor treats 0 as "use default (100)", so test the setter directly.
        $pagination = new Pagination([], 0, 6);
        $pagination->setMaxStages(0);

        self::assertSame(1, $pagination->getMaxStages());
    }

    /** @test */
    public function maxStagesLimitsNumberOfStages(): void
    {
        $pagination = new Pagination($this->makeItems(100), 0, 6, 3);

        self::assertLessThanOrEqual(3, count($pagination->getStages()->toArray()));
    }

    // ---------------------------------------------------------------------------
    // getIndicators
    // ---------------------------------------------------------------------------

    /** @test */
    public function getIndicatorsReturnsOneEntryPerStageWhenAllFilled(): void
    {
        $pagination = new Pagination($this->makeItems(12), 0, 4);

        // 12 items / 4 per stage = 3 stages → 3 indicators
        self::assertCount(3, $pagination->getIndicators());
    }

    /** @test */
    public function getIndicatorsStopsBeforePartialLastStage(): void
    {
        // 10 items, 4 per stage: stage 0=4, stage 1=4, stage 2=2 (partial)
        $pagination = new Pagination($this->makeItems(10), 0, 4);
        $indicators = $pagination->getIndicators();

        // Only stages where count >= stageLength get an indicator
        self::assertCount(2, $indicators);
    }

    /** @test */
    public function getIndicatorsReturnsEmptyArrayForNoItems(): void
    {
        $pagination = new Pagination([], 0, 6);

        self::assertSame([], $pagination->getIndicators());
    }

    // ---------------------------------------------------------------------------
    // getNextStage / getPreviousStage
    // ---------------------------------------------------------------------------

    /** @test */
    public function getNextStageReturnsIncrementedIndexWhenMoreItemsExist(): void
    {
        $pagination = new Pagination($this->makeItems(20), 0, 6);

        self::assertSame(1, $pagination->getNextStage());
    }

    /** @test */
    public function getNextStageReturnsNullWhenAllItemsShown(): void
    {
        $pagination = new Pagination($this->makeItems(6), 0, 6);

        self::assertNull($pagination->getNextStage());
    }

    /** @test */
    public function getNextStageReturnsNullAtMaxStageLimit(): void
    {
        $pagination = new Pagination($this->makeItems(100), 99, 1, 100);

        self::assertNull($pagination->getNextStage());
    }

    /** @test */
    public function getPreviousStageReturnsNullOnFirstStage(): void
    {
        $pagination = new Pagination($this->makeItems(10), 0, 5);

        self::assertNull($pagination->getPreviousStage());
    }

    /** @test */
    public function getPreviousStageReturnsDecrementedIndex(): void
    {
        $pagination = new Pagination($this->makeItems(20), 2, 5);

        self::assertSame(1, $pagination->getPreviousStage());
    }

    // ---------------------------------------------------------------------------
    // Stage object
    // ---------------------------------------------------------------------------

    /** @test */
    public function selectedStageIsMarkedAsSelected(): void
    {
        $pagination = new Pagination($this->makeItems(12), 1, 4);
        $stages = $pagination->getStages()->toArray();

        self::assertFalse($stages[0]->isSelected());
        self::assertTrue($stages[1]->isSelected());
        self::assertFalse($stages[2]->isSelected());
    }

    /** @test */
    public function allStagesUpToSelectedAreActive(): void
    {
        $pagination = new Pagination($this->makeItems(12), 1, 4);
        $stages = $pagination->getStages()->toArray();

        self::assertTrue($stages[0]->isActive());
        self::assertTrue($stages[1]->isActive());
        self::assertFalse($stages[2]->isActive());
    }

    // ---------------------------------------------------------------------------
    // Range
    // ---------------------------------------------------------------------------

    /** @test */
    public function stageRangeCalculatesCorrectFromAndTo(): void
    {
        $pagination = new Pagination($this->makeItems(12), 0, 4);
        /** @var Stage $stage1 */
        $stage1 = $pagination->getStages()->toArray()[1];
        $range = $stage1->getRange();

        self::assertSame(4, $range->getFrom());
        self::assertSame(8, $range->getTo());
        self::assertSame(4, $range->getLength());
    }
}
