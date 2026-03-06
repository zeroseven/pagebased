<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\Performance;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Middleware\QueryCountingMiddleware;
use Zeroseven\Pagebased\Utility\RootLineUtility;

/**
 * Worst-case performance tests for RootLineUtility.
 *
 * These tests verify two things:
 *   1. Query count  – how many SQL statements hit the DB in a given scenario
 *   2. Cache effect – repeated calls with the same arguments must not
 *                     issue any additional queries (static request cache)
 *
 * Fixtures:
 *   pages_deep_tree.csv  – 20-level linear chain (uid 200 → 219) plus
 *                          a 2-level wide tree (10 parents, 30 children)
 *
 * The QueryCountingMiddleware is registered as a Doctrine DBAL driver
 * middleware so every prepared-statement execution is counted regardless
 * of which code path triggers it.
 */
final class RootLineUtilityPerformanceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/pagebased'];

    /**
     * Register the counting middleware before the DB connection is created.
     * TYPO3 merges this into LocalConfiguration before bootstrapping.
     */
    protected array $configurationToUseInTestInstance = [
        'DB' => [
            'Connections' => [
                'Default' => [
                    'driverMiddlewares' => [
                        QueryCountingMiddleware::class,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages_deep_tree.csv');
        QueryCountingMiddleware::reset();
        // Clear static cache between tests so each test starts cold
        $this->resetRootLineCache();
    }

    // -------------------------------------------------------------------------
    // Query count – cold (no cache)
    // -------------------------------------------------------------------------

    /**
     * @test
     * Traversing a 20-level linear chain from the root should require at least
     * as many queries as there are levels (one per node without cache).
     * Before our static-cache fix this was always the case; after the fix it
     * still holds for the very first call (cold cache).
     */
    public function collectPagesBelowFiresMultipleQueriesForDeepTreeOnColdCache(): void
    {
        QueryCountingMiddleware::reset();

        RootLineUtility::collectPagesBelow(200);

        $queryCount = QueryCountingMiddleware::getCount();

        // Without cache a 20-level chain needs ≥ 20 queries.
        // We assert ≥ 5 to stay robust if the query planner batches some.
        self::assertGreaterThanOrEqual(5, $queryCount, sprintf(
            'Cold traversal of 20-level tree should need ≥ 5 queries, got %d',
            $queryCount
        ));
    }

    /**
     * @test
     * Traversing upward from the deepest leaf (uid 219) must touch each
     * ancestor once → at least 1 query per level on a cold cache.
     */
    public function collectPagesAboveFiresMultipleQueriesOnColdCache(): void
    {
        QueryCountingMiddleware::reset();

        RootLineUtility::collectPagesAbove(219);

        $queryCount = QueryCountingMiddleware::getCount();

        self::assertGreaterThanOrEqual(5, $queryCount, sprintf(
            'Cold traversal upward from depth-20 leaf should need ≥ 5 queries, got %d',
            $queryCount
        ));
    }

    // -------------------------------------------------------------------------
    // Query count – warm (static request cache)
    // -------------------------------------------------------------------------

    /**
     * @test
     * After the first call populates the static cache, an identical second
     * call must not issue ANY additional SQL queries.
     */
    public function collectPagesBelowIssuesZeroQueriesOnCacheHit(): void
    {
        // Warm the cache
        RootLineUtility::collectPagesBelow(200);

        // Reset counter – we only care about queries in the second call
        QueryCountingMiddleware::reset();

        RootLineUtility::collectPagesBelow(200);

        self::assertSame(0, QueryCountingMiddleware::getCount(), 'Second call with same arguments must hit cache and issue 0 queries');
    }

    /**
     * @test
     * Same for the upward traversal.
     */
    public function collectPagesAboveIssuesZeroQueriesOnCacheHit(): void
    {
        RootLineUtility::collectPagesAbove(219);
        QueryCountingMiddleware::reset();

        RootLineUtility::collectPagesAbove(219);

        self::assertSame(0, QueryCountingMiddleware::getCount(), 'Cached collectPagesAbove() must issue 0 queries');
    }

    /**
     * @test
     * Different arguments produce different cache keys – a second call with a
     * different startingPoint must NOT reuse the previous result and must
     * issue queries.
     */
    public function collectPagesBelowWithDifferentArgumentsBypassesCache(): void
    {
        // Prime cache for uid=200
        RootLineUtility::collectPagesBelow(200);
        QueryCountingMiddleware::reset();

        // uid=300 has a completely different subtree → cold miss
        RootLineUtility::collectPagesBelow(300);

        self::assertGreaterThan(0, QueryCountingMiddleware::getCount(), 'Different startingPoint must trigger a new DB query');
    }

    // -------------------------------------------------------------------------
    // Timing – cached vs uncached
    // -------------------------------------------------------------------------

    /**
     * @test
     * The cached call must not be significantly more expensive than the cold call.
     * We allow a generous factor of 5× to account for measurement noise on slow CI.
     * The zero-query guarantee is verified separately in collectPagesBelowIssuesZeroQueriesOnCacheHit().
     */
    public function cachedCallIsNotSignificantlySlowerThanColdCall(): void
    {
        // Cold call
        $start = microtime(true);
        RootLineUtility::collectPagesBelow(200);
        $coldMs = (microtime(true) - $start) * 1000;

        // Warm call
        $start = microtime(true);
        RootLineUtility::collectPagesBelow(200);
        $warmMs = (microtime(true) - $start) * 1000;

        self::assertLessThan($coldMs * 5, $warmMs, sprintf(
            'Cached call (%.3f ms) should not exceed 5× the cold call duration (%.3f ms)',
            $warmMs,
            $coldMs
        ));
    }

    /**
     * @test
     * The entire 20-level traversal must complete within a generous wall-clock
     * budget. This catches regressions that introduce blocking or sleeping.
     */
    public function collectPagesBelowCompleteWithinTimeBudget(): void
    {
        $start = microtime(true);
        RootLineUtility::collectPagesBelow(200);
        $elapsedMs = (microtime(true) - $start) * 1000;

        self::assertLessThan(2000, $elapsedMs, sprintf(
            'Traversal of 20-level tree must complete in under 2 s, took %.1f ms',
            $elapsedMs
        ));
    }

    // -------------------------------------------------------------------------
    // Result correctness – verify cache returns same data
    // -------------------------------------------------------------------------

    /**
     * @test
     * The cached result must be identical to the freshly queried one.
     */
    public function cacheReturnsSameDataAsOriginalQuery(): void
    {
        $first = RootLineUtility::collectPagesBelow(200);
        $second = RootLineUtility::collectPagesBelow(200);

        self::assertSame($first, $second, 'Cached result must be identical to first result');
        self::assertCount(19, $first, '20-level chain: uid=200 has 19 descendants (uid 201–219)');
    }

    /**
     * @test
     * collectPagesBelow for the wide tree root should return all 40 descendants
     * (10 parents + 30 grandchildren) in a single cold call.
     */
    public function collectPagesBelowReturnsAllDescendantsOfWideTree(): void
    {
        $pages = RootLineUtility::collectPagesBelow(1);

        // Tree contains: uid=200-219 (20) + uid=300-339 (40) = 60 descendants of uid=1
        self::assertGreaterThanOrEqual(60, count($pages), 'All descendants should be returned from the wide + deep tree');
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Reset the static cache on RootLineUtility via Reflection so each test
     * starts with a clean state.
     */
    private function resetRootLineCache(): void
    {
        $reflection = new \ReflectionClass(RootLineUtility::class);
        $property = $reflection->getProperty('cache');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
}
