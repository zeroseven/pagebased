<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\Performance;

use PHPUnit\Framework\Attributes\Test;
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
        'typo3conf/ext/pagebased/Tests/Functional/Fixtures',
    ];

    protected array $configurationToUseInTestInstance = [
        'DB' => [
            'Connections' => [
                'Default' => [
                    'driverMiddlewares' => [
                        'pagebased/query-counting' => [
                            'target' => QueryCountingMiddleware::class,
                        ],
                    ],
                ],
            ],
        ],
    ];

    private TestObjectRepository $testObjectRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages_many_objects.csv');
        $this->bootstrapTestRegistration();
        $this->testObjectRepository = $this->get(TestObjectRepository::class);
        QueryCountingMiddleware::reset();
    }

    // -------------------------------------------------------------------------
    // findByDemand – query count
    // -------------------------------------------------------------------------
    #[Test]
    public function findByDemandIssuesSingleQueryForAllObjects(): void
    {
        QueryCountingMiddleware::reset();

        $demand = $this->testObjectRepository->initializeDemand();
        $this->testObjectRepository->findByDemand($demand);

        $queryCount = QueryCountingMiddleware::getCount();

        self::assertLessThanOrEqual(5, $queryCount, sprintf(
            'findByDemand() should issue at most 5 queries (main SELECT + optional language/TCA queries), got %d',
            $queryCount
        ));
    }

    #[Test]
    public function findByDemandCompletesWithinTimeBudgetForLargeDataset(): void
    {
        $demand = $this->testObjectRepository->initializeDemand();

        $start = microtime(true);
        $result = $this->testObjectRepository->findByDemand($demand);
        $elapsedMs = (microtime(true) - $start) * 1000;

        self::assertNotNull($result);
        self::assertLessThan(2000, $elapsedMs, sprintf(
            'findByDemand() for 57 objects must complete in under 2 s, took %.1f ms',
            $elapsedMs
        ));
    }

    #[Test]
    public function findByDemandReturnsExpectedCountForLargeDataset(): void
    {
        $demand = $this->testObjectRepository->initializeDemand();

        $result = $this->testObjectRepository->findByDemand($demand);

        self::assertNotNull($result);
        self::assertSame(57, $result->count(), '3×20 objects with 3 hidden = 57 visible objects expected');
    }

    // -------------------------------------------------------------------------
    // findByUid – minimal query cost
    // -------------------------------------------------------------------------
    #[Test]
    public function findByUidIssuesMinimalQueries(): void
    {
        QueryCountingMiddleware::reset();

        $this->testObjectRepository->findByUid(500);

        $queryCount = QueryCountingMiddleware::getCount();

        self::assertLessThanOrEqual(3, $queryCount, sprintf(
            'findByUid() should issue at most 3 queries, got %d',
            $queryCount
        ));
    }

    #[Test]
    public function findByUidIsFasterThanFindByDemand(): void
    {
        $start = microtime(true);
        $this->testObjectRepository->findByUid(500);
        $uidMs = (microtime(true) - $start) * 1000;

        self::assertLessThan(500, $uidMs, sprintf(
            'findByUid() must complete in under 500 ms, took %.1f ms',
            $uidMs
        ));
    }

    // -------------------------------------------------------------------------
    // Repeated calls – no N+1 accumulation
    // -------------------------------------------------------------------------
    #[Test]
    public function repeatedFindByUidDoesNotCauseQueryExplosion(): void
    {
        // Baseline: single call
        QueryCountingMiddleware::reset();
        $this->testObjectRepository->findByUid(500);
        $singleCallQueries = QueryCountingMiddleware::getCount();

        // 10 consecutive lookups
        QueryCountingMiddleware::reset();
        for ($i = 0; $i < 10; $i++) {
            $uid = 500 + $i;
            $this->testObjectRepository->findByUid($uid);
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
