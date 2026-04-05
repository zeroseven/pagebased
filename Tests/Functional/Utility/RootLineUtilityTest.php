<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\Utility;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Zeroseven\Pagebased\Utility\RootLineUtility;

/**
 * Functional tests for RootLineUtility.
 *
 * These tests verify the recursive page-tree traversal used in:
 * - AbstractObjectRepository::createDemandConstraints() (collectPagesBelow)
 * - AbstractObject::getCategory() (collectPagesAbove)
 * - DetectionUtility::getUpdateFields() (collectPagesAbove via findCategoryInRootLine)
 *
 * All of these are performance-critical paths. Tests ensure correct behaviour
 * before and after caching / query optimisation.
 */
class RootLineUtilityTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/pagebased',
    ];

    protected array $coreExtensionsToLoad = [
        'core',
        'frontend',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages_tree.csv');
    }

    // ---------------------------------------------------------------------------
    // collectPagesBelow
    // ---------------------------------------------------------------------------

    /** @test */
    public function collectPagesBelowReturnsAllDirectChildren(): void
    {
        $pages = RootLineUtility::collectPagesBelow(10, false, 1);

        self::assertArrayHasKey(11, $pages);
        self::assertArrayHasKey(12, $pages);
        self::assertArrayHasKey(13, $pages);
    }

    /** @test */
    public function collectPagesBelowDoesNotIncludeStartingPointByDefault(): void
    {
        $pages = RootLineUtility::collectPagesBelow(10, false, 1);

        self::assertArrayNotHasKey(10, $pages);
    }

    /** @test */
    public function collectPagesBelowIncludesStartingPointWhenRequested(): void
    {
        $pages = RootLineUtility::collectPagesBelow(10, true, 1);

        self::assertArrayHasKey(10, $pages);
    }

    /** @test */
    public function collectPagesBelowReturnsAllDescendantsWithSufficientDepth(): void
    {
        $pages = RootLineUtility::collectPagesBelow(10, false, 2);

        // Level 1 children
        self::assertArrayHasKey(11, $pages);
        self::assertArrayHasKey(12, $pages);
        self::assertArrayHasKey(13, $pages);
        // Level 2 children
        self::assertArrayHasKey(20, $pages);
        self::assertArrayHasKey(21, $pages);
        self::assertArrayHasKey(30, $pages);
    }

    /** @test */
    public function collectPagesBelowRespectsDepthLimit(): void
    {
        // Depth 1 should not include level-2 descendants
        $pages = RootLineUtility::collectPagesBelow(10, false, 1);

        self::assertArrayNotHasKey(20, $pages);
        self::assertArrayNotHasKey(21, $pages);
        self::assertArrayNotHasKey(30, $pages);
    }

    /** @test */
    public function collectPagesBelowReturnsEmptyArrayForLeafNode(): void
    {
        $pages = RootLineUtility::collectPagesBelow(20, false, 5);

        self::assertSame([], $pages);
    }

    /** @test */
    public function collectPagesBelowDoesNotCrossIntoSiblingSubtrees(): void
    {
        // Starting from uid=11 should not return siblings 12/13 or their children
        $pages = RootLineUtility::collectPagesBelow(11, false, 5);

        self::assertArrayNotHasKey(12, $pages);
        self::assertArrayNotHasKey(13, $pages);
        self::assertArrayNotHasKey(30, $pages);
    }

    // ---------------------------------------------------------------------------
    // collectPagesAbove
    // ---------------------------------------------------------------------------

    /** @test */
    public function collectPagesAboveReturnsAncestors(): void
    {
        // uid=20 has ancestors 11, 10, 1 (not 0 = virtual root)
        $pages = RootLineUtility::collectPagesAbove(20, false, 100);

        self::assertArrayHasKey(11, $pages);
        self::assertArrayHasKey(10, $pages);
        self::assertArrayHasKey(1, $pages);
    }

    /** @test */
    public function collectPagesAboveDoesNotIncludeStartingPointByDefault(): void
    {
        $pages = RootLineUtility::collectPagesAbove(20, false, 100);

        self::assertArrayNotHasKey(20, $pages);
    }

    /** @test */
    public function collectPagesAboveIncludesStartingPointWhenRequested(): void
    {
        $pages = RootLineUtility::collectPagesAbove(20, true, 100);

        self::assertArrayHasKey(20, $pages);
    }

    /** @test */
    public function collectPagesAboveRespectsDepthLimit(): void
    {
        // Depth 1: only the direct parent (uid=11)
        $pages = RootLineUtility::collectPagesAbove(20, false, 1);

        self::assertArrayHasKey(11, $pages);
        self::assertArrayNotHasKey(10, $pages);
        self::assertArrayNotHasKey(1, $pages);
    }

    /** @test */
    public function collectPagesAboveReturnsEmptyArrayForTopLevelPage(): void
    {
        // uid=1 has pid=0 (virtual root), no real ancestors
        $pages = RootLineUtility::collectPagesAbove(1, false, 100);

        self::assertSame([], $pages);
    }

    // ---------------------------------------------------------------------------
    // getParentPage
    // ---------------------------------------------------------------------------

    /** @test */
    public function getParentPageReturnsDirectParentUid(): void
    {
        $parentUid = RootLineUtility::getParentPage(20);

        self::assertSame(11, $parentUid);
    }

    /** @test */
    public function getParentPageReturnsZeroForTopLevelPage(): void
    {
        $parentUid = RootLineUtility::getParentPage(1);

        self::assertSame(0, $parentUid);
    }

    /** @test */
    public function getParentPageReturnsDifferentParentsForSiblings(): void
    {
        $parentOf20 = RootLineUtility::getParentPage(20);
        $parentOf30 = RootLineUtility::getParentPage(30);

        self::assertSame(11, $parentOf20);
        self::assertSame(12, $parentOf30);
    }
}
