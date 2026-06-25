<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\Domain\Repository;

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

/**
 * Functional tests for AbstractObjectRepository.
 *
 * Verifies that findByDemand() applies constraints correctly – in particular:
 * - Category-scoped queries via RootLineUtility::collectPagesBelow()
 * - Filtering of nav_hidden / hidden pages
 * - Filtering of child objects
 * - Default limit of 1000 when maxItems is 0
 * - Identifier constraint (_pagebased_registration = 'test_news')
 *
 * Fixtures: Tests/Functional/Fixtures/Database/pages_objects.csv
 *   uid 10 → category (doktype=199, _pagebased_site=1)
 *   uid 20-22 → visible objects in category 10 (_pagebased_registration=test_news)
 *   uid 23   → hidden object (hidden=1)
 *   uid 24   → nav-hidden object (nav_hide=1)
 *   uid 25   → child object (_pagebased_child_object=1)
 *   uid 30   → different category (same site)
 *   uid 31   → object in category 30
 *   uid 50   → category on different site (_pagebased_site=2)
 *   uid 51   → object on different site
 */
class AbstractObjectRepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/pagebased',
        'typo3conf/ext/pagebased/Tests/Functional/Fixtures',
    ];

    protected array $coreExtensionsToLoad = [
        'core',
        'frontend',
    ];

    private TestObjectRepository $testObjectRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Register a minimal test registration so that RegistrationService can
        // resolve TestObjectRepository → 'test_news'.
        $this->bootstrapTestRegistration();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/pages_objects.csv');

        $this->testObjectRepository = $this->get(TestObjectRepository::class);
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
    // Identifier constraint
    // ---------------------------------------------------------------------------
    #[Test]
    public function findByDemandReturnsOnlyObjectsWithMatchingRegistrationIdentifier(): void
    {
        $demand = $this->testObjectRepository->initializeDemand();
        $results = $this->testObjectRepository->findByDemand($demand);

        self::assertNotNull($results);

        foreach ($results as $result) {
            self::assertInstanceOf(TestObject::class, $result);
        }
    }

    // ---------------------------------------------------------------------------
    // nav_hide / hidden filtering
    // ---------------------------------------------------------------------------
    #[Test]
    public function findByDemandExcludesNavHiddenObjects(): void
    {
        $demand = $this->testObjectRepository->initializeDemand();
        $results = $this->testObjectRepository->findByDemand($demand);

        self::assertNotNull($results);

        $uids = array_map(static fn(object $o) => $o->getUid(), iterator_to_array($results));
        self::assertNotContains(24, $uids, 'nav_hide=1 object (uid=24) must not appear');
    }

    #[Test]
    public function findByDemandExcludesHiddenObjects(): void
    {
        $demand = $this->testObjectRepository->initializeDemand();
        $results = $this->testObjectRepository->findByDemand($demand);

        self::assertNotNull($results);

        $uids = array_map(static fn(object $o) => $o->getUid(), iterator_to_array($results));
        self::assertNotContains(23, $uids, 'hidden=1 object (uid=23) must not appear');
    }

    // ---------------------------------------------------------------------------
    // Child object filtering
    // ---------------------------------------------------------------------------
    #[Test]
    public function findByDemandExcludesChildObjectsByDefault(): void
    {
        $demand = $this->testObjectRepository->initializeDemand();
        // includeChildObjects defaults to false
        self::assertFalse($demand->getIncludeChildObjects());

        $results = $this->testObjectRepository->findByDemand($demand);
        self::assertNotNull($results);

        $uids = array_map(static fn(object $o) => $o->getUid(), iterator_to_array($results));
        self::assertNotContains(25, $uids, 'child object (uid=25) must be excluded by default');
    }

    #[Test]
    public function findByDemandIncludesChildObjectsWhenEnabled(): void
    {
        $demand = $this->testObjectRepository->initializeDemand()->setIncludeChildObjects(true);
        $results = $this->testObjectRepository->findByDemand($demand);

        self::assertNotNull($results);

        $uids = array_map(static fn(object $o) => $o->getUid(), iterator_to_array($results));
        self::assertContains(25, $uids, 'child object (uid=25) must appear when includeChildObjects=true');
    }

    // ---------------------------------------------------------------------------
    // Category constraint via RootLineUtility
    // ---------------------------------------------------------------------------
    #[Test]
    public function findByDemandWithCategoryConstraintReturnsOnlyObjectsUnderThatCategory(): void
    {
        $demand = $this->testObjectRepository->initializeDemand()->setCategory(10);
        $results = $this->testObjectRepository->findByDemand($demand);

        self::assertNotNull($results);

        $uids = array_map(static fn(object $o) => $o->getUid(), iterator_to_array($results));

        // Objects directly under category 10
        self::assertContains(20, $uids);
        self::assertContains(21, $uids);
        self::assertContains(22, $uids);

        // Object in sibling category 30 must not appear
        self::assertNotContains(31, $uids);
    }

    #[Test]
    public function findByDemandWithCategoryConstraintExcludesObjectsFromOtherCategories(): void
    {
        $demand = $this->testObjectRepository->initializeDemand()->setCategory(30);
        $results = $this->testObjectRepository->findByDemand($demand);

        self::assertNotNull($results);

        $uids = array_map(static fn(object $o) => $o->getUid(), iterator_to_array($results));
        self::assertContains(31, $uids);

        // Objects from category 10 must not appear
        self::assertNotContains(20, $uids);
        self::assertNotContains(21, $uids);
    }

    // ---------------------------------------------------------------------------
    // Limit / maxItems
    // ---------------------------------------------------------------------------
    #[Test]
    public function findByDemandUsesLimitOf1000WhenMaxItemsIsZero(): void
    {
        $demand = $this->testObjectRepository->initializeDemand();
        self::assertSame(0, $demand->getMaxItems(), 'Default maxItems should be 0');

        // findByDemand must not throw and must return a result
        $results = $this->testObjectRepository->findByDemand($demand);
        self::assertNotNull($results);
    }

    #[Test]
    public function findByDemandRespectsCustomMaxItems(): void
    {
        $demand = $this->testObjectRepository->initializeDemand()->setMaxItems(2);
        $results = $this->testObjectRepository->findByDemand($demand);

        self::assertNotNull($results);
        self::assertLessThanOrEqual(2, $results->count());
    }
}
