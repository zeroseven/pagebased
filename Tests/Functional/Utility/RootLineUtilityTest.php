<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\Utility;

use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function collectPagesBelowReturnsAllDirectChildren(): void
    {
        $pages = RootLineUtility::collectPagesBelow(10, false, 1);

        self::assertArrayHasKey(11, $pages);
        self::assertArrayHasKey(12, $pages);
        self::assertArrayHasKey(13, $pages);
    }

    #[Test]
    public function collectPagesBelowDoesNotIncludeStartingPointByDefault(): void
    {
        $pages = RootLineUtility::collectPagesBelow(10, false, 1);

        self::assertArrayNotHasKey(10, $pages);
    }

    #[Test]
    public function collectPagesBelowIncludesStartingPointWhenRequested(): void
    {
        $pages = RootLineUtility::collectPagesBelow(10, true, 1);

        self::assertArrayHasKey(10, $pages);
    }

    #[Test]
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

    #[Test]
    public function collectPagesBelowRespectsDepthLimit(): void
    {
        // Depth 1 should not include level-2 descendants
        $pages = RootLineUtility::collectPagesBelow(10, false, 1);

        self::assertArrayNotHasKey(20, $pages);
        self::assertArrayNotHasKey(21, $pages);
        self::assertArrayNotHasKey(30, $pages);
    }

    #[Test]
    public function collectPagesBelowReturnsEmptyArrayForLeafNode(): void
    {
        $pages = RootLineUtility::collectPagesBelow(20, false, 5);

        self::assertSame([], $pages);
    }

    #[Test]
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
    #[Test]
    public function collectPagesAboveReturnsAncestors(): void
    {
        // uid=20 has ancestors 11, 10, 1 (not 0 = virtual root)
        $pages = RootLineUtility::collectPagesAbove(20, false, 100);

        self::assertArrayHasKey(11, $pages);
        self::assertArrayHasKey(10, $pages);
        self::assertArrayHasKey(1, $pages);
    }

    #[Test]
    public function collectPagesAboveDoesNotIncludeStartingPointByDefault(): void
    {
        $pages = RootLineUtility::collectPagesAbove(20, false, 100);

        self::assertArrayNotHasKey(20, $pages);
    }

    #[Test]
    public function collectPagesAboveIncludesStartingPointWhenRequested(): void
    {
        $pages = RootLineUtility::collectPagesAbove(20, true, 100);

        self::assertArrayHasKey(20, $pages);
    }

    #[Test]
    public function collectPagesAboveRespectsDepthLimit(): void
    {
        // Depth 1: only the direct parent (uid=11)
        $pages = RootLineUtility::collectPagesAbove(20, false, 1);

        self::assertArrayHasKey(11, $pages);
        self::assertArrayNotHasKey(10, $pages);
        self::assertArrayNotHasKey(1, $pages);
    }

    #[Test]
    public function collectPagesAboveReturnsEmptyArrayForTopLevelPage(): void
    {
        // uid=1 has pid=0 (virtual root), no real ancestors
        $pages = RootLineUtility::collectPagesAbove(1, false, 100);

        self::assertSame([], $pages);
    }

    // ---------------------------------------------------------------------------
    // getParentPage
    // ---------------------------------------------------------------------------
    #[Test]
    public function getParentPageReturnsDirectParentUid(): void
    {
        $parentUid = RootLineUtility::getParentPage(20);

        self::assertSame(11, $parentUid);
    }

    #[Test]
    public function getParentPageReturnsZeroForTopLevelPage(): void
    {
        $parentUid = RootLineUtility::getParentPage(1);

        self::assertSame(0, $parentUid);
    }

    #[Test]
    public function getParentPageReturnsDifferentParentsForSiblings(): void
    {
        $parentOf20 = RootLineUtility::getParentPage(20);
        $parentOf30 = RootLineUtility::getParentPage(30);

        self::assertSame(11, $parentOf20);
        self::assertSame(12, $parentOf30);
    }

    // ---------------------------------------------------------------------------
    // Language / translation handling
    // ---------------------------------------------------------------------------
    #[Test]
    public function collectPagesBelowExcludesTranslatedPages(): void
    {
        // Fixture adds uid=40 (pid=12, sys_language_uid=1, l10n_parent=30).
        // collectPagesBelow must return only default-language pages (sys_language_uid=0).
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages_tree_with_translation.csv');

        $pages = RootLineUtility::collectPagesBelow(12);

        self::assertArrayHasKey(30, $pages, 'Default-language child uid=30 must be returned');
        self::assertArrayNotHasKey(40, $pages, 'Translated page uid=40 (sys_language_uid=1) must NOT be returned');
    }

    #[Test]
    public function collectPagesAboveExcludesTranslatedPages(): void
    {
        // Fixture: uid=40 is a translation of uid=30, child of uid=12.
        // Walking up from uid=20 (ancestor chain: 11 → 10 → 1) must not include
        // any translated page.
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages_tree_with_translation.csv');

        $pages = RootLineUtility::collectPagesAbove(20);

        foreach ($pages as $page) {
            self::assertSame(0, (int)($page['sys_language_uid'] ?? 0), 'Ancestor uid=' . $page['uid'] . ' must be default language (sys_language_uid=0)');
        }
    }
}
