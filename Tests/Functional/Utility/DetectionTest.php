<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\Utility;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Utility\DetectionUtility;
use Zeroseven\Pagebased\Utility\ObjectUtility;

/**
 * Verifies the page classification / detection core:
 *  - PageClassifier::isCategory() recognises a page by its registered category documentType (188,
 *    provided by the RegistrationFixture extension);
 *  - DetectionUtility writes the registration identifier + site onto pages that live below a
 *    category in the rootline, and leaves pages outside any category untouched.
 *
 * The page tree (pages_detection.csv) ships with empty _pagebased_* columns, so the assertions
 * reflect what detection actually computes – not pre-seeded values.
 */
class DetectionTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/pagebased',
        'typo3conf/ext/pagebased/Tests/Functional/RegistrationFixture',
    ];

    protected array $coreExtensionsToLoad = [
        'core',
        'backend',
        'frontend',
        'extbase',
        'fluid',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages_detection.csv');
    }

    /** @return array{_pagebased_registration: string, _pagebased_site: int, _pagebased_child_object: int} */
    private function detectionFields(int $uid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select('_pagebased_registration', '_pagebased_site', '_pagebased_child_object')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $uid))
            ->executeQuery()
            ->fetchAssociative();

        return [
            '_pagebased_registration' => (string)($row['_pagebased_registration'] ?? ''),
            '_pagebased_site' => (int)($row['_pagebased_site'] ?? 0),
            '_pagebased_child_object' => (int)($row['_pagebased_child_object'] ?? 0),
        ];
    }

    #[Test]
    public function isCategoryRecognisesPagesByRegisteredDocumentType(): void
    {
        self::assertInstanceOf(Registration::class, ObjectUtility::isCategory(10), 'doktype 188 page must be a category');
        self::assertNull(ObjectUtility::isCategory(11), 'an object page (doktype 1) is not a category');
        self::assertNull(ObjectUtility::isCategory(20), 'a plain page is not a category');
    }

    #[Test]
    public function detectionMarksObjectsBelowACategory(): void
    {
        foreach ([10, 11, 12, 20] as $uid) {
            DetectionUtility::updateFields($uid);
        }

        self::assertSame('RegistrationFixture', $this->detectionFields(11)['_pagebased_registration'], 'object below category gets the registration identifier');
        self::assertSame('RegistrationFixture', $this->detectionFields(12)['_pagebased_registration']);
        self::assertSame('RegistrationFixture', $this->detectionFields(10)['_pagebased_registration'], 'the category page itself belongs to the registration');
    }

    #[Test]
    public function detectionLeavesPagesOutsideAnyCategoryUntouched(): void
    {
        foreach ([10, 11, 12, 20] as $uid) {
            DetectionUtility::updateFields($uid);
        }

        self::assertSame('', $this->detectionFields(20)['_pagebased_registration'], 'a page that is not below a category must not be marked');
        self::assertSame(0, $this->detectionFields(20)['_pagebased_site']);
    }

    #[Test]
    public function detectionStoresTheSiteRootForObjects(): void
    {
        DetectionUtility::updateFields(11);

        self::assertSame(1, $this->detectionFields(11)['_pagebased_site'], 'detected object stores its site root page id');
    }
}
