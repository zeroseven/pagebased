<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\Domain\Repository;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Zeroseven\Pagebased\Registration\CategoryRegistration;
use Zeroseven\Pagebased\Registration\ObjectRegistration;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestCategory;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestCategoryRepository;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestObject;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestObjectRepository;

/**
 * Functional tests for AbstractObjectRepository::findTagStrings().
 *
 * Verifies that findTagStrings():
 * - Returns raw tag strings for all visible, non-category pages
 * - Filters results when a category UID is set on the demand
 * - Returns an empty array when a category has no child pages
 * - Produces identical results on a second call (cache-hit behaviour)
 *
 * Fixtures: Tests/Functional/Fixtures/Database/pages_many_objects.csv
 *   uid 400 → Category 1 (doktype=199), parent of objects 500-519
 *   uid 401 → Category 2, parent of objects 520-539
 *   uid 402 → Category 3, parent of objects 540-559
 *   Last object in each category is hidden (uid 519, 539, 559)
 *   → 57 visible objects with non-empty pagebased_tags
 */
class FindTagStringsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/pagebased',
    ];

    protected array $coreExtensionsToLoad = [
        'core',
        'frontend',
    ];

    private TestObjectRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootstrapTestRegistration();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/pages_many_objects.csv');

        $this->repository = $this->get(TestObjectRepository::class);
    }

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

    // ---------------------------------------------------------------------------
    // Return value correctness
    // ---------------------------------------------------------------------------

    /** @test */
    public function findTagStringsReturnsArrayOfStrings(): void
    {
        $demand = $this->repository->initializeDemand();
        $result = $this->repository->findTagStrings($demand);

        self::assertIsArray($result);
        self::assertNotEmpty($result, 'Expected at least one tag string in fixture data');

        foreach ($result as $tagString) {
            self::assertIsString($tagString, 'Each element must be a raw tag string');
            self::assertNotSame('', $tagString, 'Empty tag strings must be excluded by the query');
        }
    }

    /** @test */
    public function findTagStringsReturnsExpectedCountForKnownDataset(): void
    {
        $demand = $this->repository->initializeDemand();
        $result = $this->repository->findTagStrings($demand);

        // 3 categories × 20 objects = 60 total; last in each category is hidden → 57 visible
        self::assertCount(57, $result, '57 visible objects with non-empty tags expected');
    }

    // ---------------------------------------------------------------------------
    // Category filtering
    // ---------------------------------------------------------------------------

    /** @test */
    public function findTagStringsWithCategoryFilterReturnsSubsetOfAllResults(): void
    {
        $allDemand = $this->repository->initializeDemand();
        $allResults = $this->repository->findTagStrings($allDemand);

        $categoryDemand = $this->repository->initializeDemand()->setCategory(400);
        $categoryResults = $this->repository->findTagStrings($categoryDemand);

        self::assertNotEmpty($categoryResults, 'Category 400 must have objects with tags');
        self::assertLessThan(
            count($allResults),
            count($categoryResults),
            'Category-scoped result must be a strict subset of all results'
        );
    }

    /** @test */
    public function findTagStringsWithCategoryFilterReturnsOnlyObjectsUnderThatCategory(): void
    {
        // Category 400 has 20 objects (UIDs 500–519), last one is hidden → 19 visible
        $demand = $this->repository->initializeDemand()->setCategory(400);
        $result = $this->repository->findTagStrings($demand);

        self::assertCount(19, $result, 'Category 400 must yield exactly 19 visible tag strings');
    }

    /** @test */
    public function findTagStringsReturnsEmptyArrayForCategoryWithNoChildPages(): void
    {
        // UID 9999 does not exist in the fixture, so collectPagesBelow() returns [].
        $demand = $this->repository->initializeDemand()->setCategory(9999);
        $result = $this->repository->findTagStrings($demand);

        self::assertSame([], $result, 'Non-existent category must yield an empty array');
    }

    // ---------------------------------------------------------------------------
    // Cache-hit behaviour
    // ---------------------------------------------------------------------------

    /** @test */
    public function findTagStringsReturnsSameResultOnConsecutiveCalls(): void
    {
        $demand = $this->repository->initializeDemand();

        $firstCall = $this->repository->findTagStrings($demand);
        $secondCall = $this->repository->findTagStrings($demand);

        self::assertSame(
            $firstCall,
            $secondCall,
            'Consecutive calls with identical demand must return the same result (cache hit)'
        );
    }

    /** @test */
    public function findTagStringsWithCategoryFilterReturnsSameResultOnConsecutiveCalls(): void
    {
        $demand = $this->repository->initializeDemand()->setCategory(401);

        $firstCall = $this->repository->findTagStrings($demand);
        $secondCall = $this->repository->findTagStrings($demand);

        self::assertSame(
            $firstCall,
            $secondCall,
            'Consecutive category-filtered calls must return the same result (cache hit)'
        );
    }
}
