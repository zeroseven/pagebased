<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\Utility;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Zeroseven\Pagebased\Registration\CategoryRegistration;
use Zeroseven\Pagebased\Registration\ObjectRegistration;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestCategory;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestCategoryRepository;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestObject;
use Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes\TestObjectRepository;
use Zeroseven\Pagebased\Utility\RootLineUtility;
use Zeroseven\Pagebased\Utility\TagUtility;

/**
 * Functional tests for TagUtility::getTags() with the pagebased.nonglobalTags feature flag.
 *
 * Fixture layout (pages_tags.csv):
 *   uid  1  → Site root (doktype=1)
 *   uid 10  → Category A (doktype=199)
 *   uid 20  → Object in Category A, tags: "php,typo3"
 *   uid 21  → Object in Category A, tags: "php,symfony"
 *   uid 30  → Category B (doktype=199)
 *   uid 31  → Object in Category B, tags: "javascript,typo3"
 *   uid 40  → Standalone page (doktype=1, no registered category in rootline)
 *
 * Feature flag OFF (default): tags are collected globally across all categories.
 * Feature flag ON:
 *   - Category already set in demand → rootline detection is skipped.
 *   - No category in demand, category page found in rootline → demand is scoped automatically.
 *   - No category in demand, no category page in rootline → null returned.
 */
class TagUtilityTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages_tags.csv');

        $this->repository = $this->get(TestObjectRepository::class);

        // Ensure the feature flag is off by default for each test.
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['pagebased.nonglobalTags'] = false;
    }

    protected function tearDown(): void
    {
        // Clear the static RootLineUtility cache so page-context changes between
        // tests do not bleed into one another.
        $reflection = new \ReflectionProperty(RootLineUtility::class, 'cache');
        $reflection->setValue(null, []);

        unset($_GET['id']);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['pagebased.nonglobalTags'] = false;

        parent::tearDown();
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
    // Feature flag OFF (default behaviour)
    // ---------------------------------------------------------------------------

    /** @test */
    public function getTagsReturnsGlobalTagsWhenFeatureFlagIsOff(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['pagebased.nonglobalTags'] = false;

        $demand = $this->repository->initializeDemand();
        $tags = TagUtility::getTags($demand, $this->repository);

        // All objects across all categories are included: php, typo3, symfony, javascript
        self::assertNotNull($tags);
        self::assertContains('php', $tags);
        self::assertContains('typo3', $tags);
        self::assertContains('symfony', $tags);
        self::assertContains('javascript', $tags);
        self::assertSame(['javascript', 'php', 'symfony', 'typo3'], $tags);
    }

    // ---------------------------------------------------------------------------
    // Feature flag ON – rootline detection active
    // ---------------------------------------------------------------------------

    /** @test */
    public function getTagsScopesTagsToCategoryFoundInRootlineWhenFlagIsOn(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['pagebased.nonglobalTags'] = true;

        // Simulate browsing object page 20 (child of Category A, uid=10)
        $_GET['id'] = '20';

        $demand = $this->repository->initializeDemand();
        $tags = TagUtility::getTags($demand, $this->repository);

        // Only objects in Category A (uid 20, 21): php, typo3, symfony
        self::assertNotNull($tags);
        self::assertSame(['php', 'symfony', 'typo3'], $tags);
        self::assertNotContains('javascript', $tags);
    }

    /** @test */
    public function getTagsReturnsNullWhenFlagIsOnButNoCategoryPageInRootline(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['pagebased.nonglobalTags'] = true;

        // Simulate browsing a standalone page (uid=40) with no registered category ancestor
        $_GET['id'] = '40';

        $demand = $this->repository->initializeDemand();
        $result = TagUtility::getTags($demand, $this->repository);

        self::assertNull($result);
    }

    /** @test */
    public function getTagsSkipsRootlineDetectionWhenCategoryAlreadySetInDemand(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['pagebased.nonglobalTags'] = true;

        // Category B (uid=30) is set explicitly – rootline should not be consulted.
        // $_GET['id'] points to a page outside any category to prove rootline is skipped.
        $_GET['id'] = '40';

        $demand = $this->repository->initializeDemand();
        $demand->{'setCategory'}(30);

        $tags = TagUtility::getTags($demand, $this->repository);

        // Only objects in Category B (uid 31): javascript, typo3
        self::assertNotNull($tags);
        self::assertSame(['javascript', 'typo3'], $tags);
        self::assertNotContains('php', $tags);
        self::assertNotContains('symfony', $tags);
    }

    /** @test */
    public function getTagsOnCategoryPageItselfResolvesCorrectlyWhenFlagIsOn(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['pagebased.nonglobalTags'] = true;

        // Simulate browsing the Category A page itself (uid=10, doktype=199)
        $_GET['id'] = '10';

        $demand = $this->repository->initializeDemand();
        $tags = TagUtility::getTags($demand, $this->repository);

        // collectPagesAbove with includingStartingPoint=true includes uid=10 itself
        self::assertNotNull($tags);
        self::assertSame(['php', 'symfony', 'typo3'], $tags);
    }
}
