<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Unit\Utility;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Zeroseven\Pagebased\Domain\Model\ObjectInterface;
use Zeroseven\Pagebased\Utility\TagUtility;

/**
 * Tests for TagUtility::collectTagsFromQueryResult().
 *
 * This is the central method called during filterAction() to collect all tags
 * for the filter UI. It iterates ALL objects – a known performance issue.
 * Tests ensure correctness before we optimise the underlying query.
 */
class TagUtilityTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * Creates a mock ObjectInterface with a given set of tags.
     *
     * @param string[] $tags
     * @return ObjectInterface&MockObject
     */
    private function makeObjectWithTags(array $tags): ObjectInterface
    {
        $object = $this->createMock(ObjectInterface::class);
        $object->method('getTags')->willReturn($tags);

        return $object;
    }

    /**
     * Creates a mock QueryResultInterface that returns the given objects.
     *
     * @param ObjectInterface[] $objects
     * @return QueryResultInterface&MockObject
     */
    private function makeQueryResult(array $objects): QueryResultInterface
    {
        $queryResult = $this->createMock(QueryResultInterface::class);
        $queryResult->method('toArray')->willReturn($objects);

        return $queryResult;
    }

    // ---------------------------------------------------------------------------
    // collectTagsFromQueryResult
    // ---------------------------------------------------------------------------

    /** @test */
    public function collectTagsReturnsEmptyArrayForEmptyResult(): void
    {
        $result = TagUtility::collectTagsFromQueryResult($this->makeQueryResult([]));

        self::assertSame([], $result);
    }

    /** @test */
    public function collectTagsReturnsSingleObjectTags(): void
    {
        $queryResult = $this->makeQueryResult([
            $this->makeObjectWithTags(['php', 'typo3']),
        ]);

        $tags = TagUtility::collectTagsFromQueryResult($queryResult);

        self::assertContains('php', $tags);
        self::assertContains('typo3', $tags);
    }

    /** @test */
    public function collectTagsMergesTagsFromMultipleObjects(): void
    {
        $queryResult = $this->makeQueryResult([
            $this->makeObjectWithTags(['php', 'typo3']),
            $this->makeObjectWithTags(['symfony', 'php']),
        ]);

        $tags = TagUtility::collectTagsFromQueryResult($queryResult);

        self::assertContains('php', $tags);
        self::assertContains('typo3', $tags);
        self::assertContains('symfony', $tags);
    }

    /** @test */
    public function collectTagsRemovesDuplicates(): void
    {
        $queryResult = $this->makeQueryResult([
            $this->makeObjectWithTags(['php', 'typo3']),
            $this->makeObjectWithTags(['php', 'typo3']),
            $this->makeObjectWithTags(['php']),
        ]);

        $tags = TagUtility::collectTagsFromQueryResult($queryResult);

        self::assertCount(2, $tags);
        self::assertContains('php', $tags);
        self::assertContains('typo3', $tags);
    }

    /** @test */
    public function collectTagsReturnsSortedAlphabetically(): void
    {
        $queryResult = $this->makeQueryResult([
            $this->makeObjectWithTags(['zebra', 'apple', 'mango']),
        ]);

        $tags = TagUtility::collectTagsFromQueryResult($queryResult);

        self::assertSame(['apple', 'mango', 'zebra'], $tags);
    }

    /** @test */
    public function collectTagsReturnsSortedAndDeduplicatedAcrossMultipleObjects(): void
    {
        $queryResult = $this->makeQueryResult([
            $this->makeObjectWithTags(['mango', 'apple']),
            $this->makeObjectWithTags(['zebra', 'apple']),
            $this->makeObjectWithTags(['banana', 'mango']),
        ]);

        $tags = TagUtility::collectTagsFromQueryResult($queryResult);

        self::assertSame(['apple', 'banana', 'mango', 'zebra'], $tags);
    }

    /** @test */
    public function collectTagsHandlesObjectsWithEmptyTagArrays(): void
    {
        $queryResult = $this->makeQueryResult([
            $this->makeObjectWithTags([]),
            $this->makeObjectWithTags(['php']),
            $this->makeObjectWithTags([]),
        ]);

        $tags = TagUtility::collectTagsFromQueryResult($queryResult);

        self::assertSame(['php'], $tags);
    }

    /** @test */
    public function collectTagsResultIsIndexedFromZero(): void
    {
        // After deduplication and sort, the result array must be re-indexed
        $queryResult = $this->makeQueryResult([
            $this->makeObjectWithTags(['b', 'a']),
        ]);

        $tags = TagUtility::collectTagsFromQueryResult($queryResult);

        self::assertArrayHasKey(0, $tags);
        self::assertArrayHasKey(1, $tags);
    }

    // ---------------------------------------------------------------------------
    // collectTagsFromStrings
    // ---------------------------------------------------------------------------

    /** @test */
    public function collectTagsFromStringsReturnsEmptyArrayForEmptyInput(): void
    {
        self::assertSame([], TagUtility::collectTagsFromStrings([]));
    }

    /** @test */
    public function collectTagsFromStringsParsesSingleCsvRow(): void
    {
        $tags = TagUtility::collectTagsFromStrings(['php,typo3']);

        self::assertContains('php', $tags);
        self::assertContains('typo3', $tags);
        self::assertCount(2, $tags);
    }

    /** @test */
    public function collectTagsFromStringsMergesAndDeduplicatesAcrossRows(): void
    {
        $tags = TagUtility::collectTagsFromStrings(['php,typo3', 'symfony,php']);

        self::assertSame(['php', 'symfony', 'typo3'], $tags);
    }

    /** @test */
    public function collectTagsFromStringsSortedAlphabetically(): void
    {
        $tags = TagUtility::collectTagsFromStrings(['zebra,apple,mango']);

        self::assertSame(['apple', 'mango', 'zebra'], $tags);
    }

    /** @test */
    public function collectTagsFromStringsIgnoresEmptySegments(): void
    {
        $tags = TagUtility::collectTagsFromStrings([',php,', 'typo3,']);

        self::assertSame(['php', 'typo3'], $tags);
    }
}
