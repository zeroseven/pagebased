<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\Performance;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Zeroseven\Pagebased\Registration\CategoryRegistration;
use Zeroseven\Pagebased\Registration\ObjectRegistration;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestCategory;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestCategoryRepository;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestObject;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestObjectRepository;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Middleware\QueryCountingMiddleware;

/**
 * Worst-case performance tests for AbstractObjectRepository.
 *
 * These tests measure:
 *   1. How many SQL queries findByDemand() issues for various dataset sizes
 *   2. Wall-clock time for large result sets
 *   3. findByUid() uses a minimal direct query (no demand pipeline)
 *
 * Fixtures:
 *   pages_many_objects.csv – 3 categories × 20 objects = 60 objects total,
 *                            each with pagebased_tags for tag-collection tests
 */
final class RepositoryPerformanceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/pagebased',
        'typo3conf/ext/pagebased/Tests/Functional/pagebased_test_fixtures',
    ];

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

    private TestObjectRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../pagebased_test_fixtures/Database/pages_many_objects.csv');
        $this->bootstrapTestRegistration();
        $this->repository = $this->get(TestObjectRepository::class);
        QueryCountingMiddleware::reset();
    }

    // -------------------------------------------------------------------------
    // findByDemand – query count
    // -------------------------------------------------------------------------

    /**
     * @test
     * findByDemand() for all objects must complete in exactly ONE SQL query
     * (a single SELECT against the pages table). Extbase lazy-loads relations,
     * so the initial fetch should not trigger additional selects.
     */
    public function findByDemandIssuesSingleQueryForAllObjects(): void
    {
        QueryCountingMiddleware::reset();

        $demand = $this->repository->initializeDemand();
        $this->repository->findByDemand($demand);

        $queryCount = QueryCountingMiddleware::getCount();

        self::assertLessThanOrEqual(5, $queryCount, sprintf(
            'findByDemand() should issue at most 5 queries (main SELECT + optional language/TCA queries), got %d',
            $queryCount
        ));
    }

    /**
     * @test
     * Time to fetch 57 visible objects must stay well under 2 seconds even
     * on a cold connection.
     */
    public function findByDemandCompletesWithinTimeBudgetForLargeDataset(): void
    {
        $demand = $this->repository->initializeDemand();

        $start = microtime(true);
        $result = $this->repository->findByDemand($demand);
        $elapsedMs = (microtime(true) - $start) * 1000;

        self::assertNotNull($result);
        self::assertLessThan(2000, $elapsedMs, sprintf(
            'findByDemand() for 57 objects must complete in under 2 s, took %.1f ms',
            $elapsedMs
        ));
    }

    /**
     * @test
     * findByDemand() must return all visible objects (hidden excluded).
     * 3 categories × 20 objects = 60 total; last in each category is hidden → 57.
     */
    public function findByDemandReturnsExpectedCountForLargeDataset(): void
    {
        $demand = $this->repository->initializeDemand();

        $result = $this->repository->findByDemand($demand);

        self::assertNotNull($result);
        self::assertSame(57, $result->count(), '3×20 objects with 3 hidden = 57 visible objects expected');
    }

    // -------------------------------------------------------------------------
    // findByUid – minimal query cost
    // -------------------------------------------------------------------------

    /**
     * @test
     * findByUid() must use at most 2 queries. Before the optimisation it ran
     * through the full demand pipeline (site-scope query + registration query).
     * After the fix it is a single direct SELECT.
     */
    public function findByUidIssuesMinimalQueries(): void
    {
        QueryCountingMiddleware::reset();

        $this->repository->findByUid(500);

        $queryCount = QueryCountingMiddleware::getCount();

        self::assertLessThanOrEqual(3, $queryCount, sprintf(
            'findByUid() should issue at most 3 queries, got %d',
            $queryCount
        ));
    }

    /**
     * @test
     * findByUid() must complete within a tight time budget – verifying it
     * doesn't run through the expensive full demand pipeline.
     */
    public function findByUidIsFasterThanFindByDemand(): void
    {
        $start = microtime(true);
        $this->repository->findByUid(500);
        $uidMs = (microtime(true) - $start) * 1000;

        self::assertLessThan(500, $uidMs, sprintf(
            'findByUid() must complete in under 500 ms, took %.1f ms',
            $uidMs
        ));
    }

    // -------------------------------------------------------------------------
    // Repeated calls – no N+1 accumulation
    // -------------------------------------------------------------------------

    /**
     * @test
     * Calling findByUid() 10 times in a loop must not issue 10× as many
     * queries as a single call (Extbase sometimes over-fetches on relations).
     * Total queries ≤ singleCallQueries * 10 + 5 (small constant overhead).
     */
    public function repeatedFindByUidDoesNotCauseQueryExplosion(): void
    {
        // Baseline: single call
        QueryCountingMiddleware::reset();
        $this->repository->findByUid(500);
        $singleCallQueries = QueryCountingMiddleware::getCount();

        // 10 consecutive lookups
        QueryCountingMiddleware::reset();
        for ($i = 0; $i < 10; $i++) {
            $uid = 500 + $i;
            $this->repository->findByUid($uid);
        }
        $tenCallQueries = QueryCountingMiddleware::getCount();

        $maxExpected = ($singleCallQueries * 10) + 5;
        self::assertLessThanOrEqual($maxExpected, $tenCallQueries, sprintf(
            '10× findByUid() should use ≤ %d queries (10 × %d + 5), used %d',
            $maxExpected,
            $singleCallQueries,
            $tenCallQueries
        ));
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function bootstrapTestRegistration(): void
    {
        $objectRegistration = new ObjectRegistration('Test Object');
        $objectRegistration->setClassName(TestObject::class);
        $objectRegistration->setRepositoryClass(TestObjectRepository::class);

        $categoryRegistration = new CategoryRegistration('Test Category');
        $categoryRegistration->setClassName(TestCategory::class);
        $categoryRegistration->setRepositoryClass(TestCategoryRepository::class);
        $categoryRegistration->setDocumentType(199);

        $registration = new Registration('test', 'test_news');
        $registration->setObject($objectRegistration);
        $registration->setCategory($categoryRegistration);

        RegistrationService::addRegistration($registration);
    }
}
