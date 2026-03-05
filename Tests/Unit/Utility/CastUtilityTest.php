<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Unit\Utility;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Zeroseven\Pagebased\Exception\TypeException;
use Zeroseven\Pagebased\Utility\CastUtility;

/**
 * Tests for CastUtility.
 *
 * CastUtility is used throughout the extension for type-safe casting,
 * most critically in findByUid(), findByUidList(), and the demand system.
 */
class CastUtilityTest extends UnitTestCase
{
    // ---------------------------------------------------------------------------
    // int()
    // ---------------------------------------------------------------------------

    /** @test */
    public function intCastsIntegerValue(): void
    {
        self::assertSame(42, CastUtility::int(42));
    }

    /** @test */
    public function intCastsStringInteger(): void
    {
        self::assertSame(7, CastUtility::int('7'));
    }

    /** @test */
    public function intCastsZero(): void
    {
        self::assertSame(0, CastUtility::int(0));
    }

    /** @test */
    public function intCastsNullToZero(): void
    {
        self::assertSame(0, CastUtility::int(null));
    }

    /** @test */
    public function intCastsEmptyStringToZero(): void
    {
        self::assertSame(0, CastUtility::int(''));
    }

    /** @test */
    public function intThrowsTypeExceptionForNonNumericString(): void
    {
        $this->expectException(TypeException::class);
        CastUtility::int('not-a-number');
    }

    /** @test */
    public function intThrowsTypeExceptionForArray(): void
    {
        $this->expectException(TypeException::class);
        CastUtility::int([1, 2, 3]);
    }

    // ---------------------------------------------------------------------------
    // string()
    // ---------------------------------------------------------------------------

    /** @test */
    public function stringCastsStringValue(): void
    {
        self::assertSame('hello', CastUtility::string('hello'));
    }

    /** @test */
    public function stringCastsIntegerToString(): void
    {
        self::assertSame('42', CastUtility::string(42));
    }

    /** @test */
    public function stringCastsNullToEmptyString(): void
    {
        self::assertSame('', CastUtility::string(null));
    }

    /** @test */
    public function stringJoinsArrayWithComma(): void
    {
        self::assertSame('a,b,c', CastUtility::string(['a', 'b', 'c']));
    }

    // ---------------------------------------------------------------------------
    // array()
    // ---------------------------------------------------------------------------

    /** @test */
    public function arrayReturnsArrayAsIs(): void
    {
        $input = [1, 2, 3];
        self::assertSame($input, CastUtility::array($input));
    }

    /** @test */
    public function arraySplitsCommaSeparatedString(): void
    {
        self::assertSame(['a', 'b', 'c'], CastUtility::array('a,b,c'));
    }

    /** @test */
    public function arraySplitsStringWithCustomDelimiter(): void
    {
        self::assertSame(['tag1', 'tag2', 'tag3'], CastUtility::array('tag1,tag2,tag3', ','));
    }

    /** @test */
    public function arrayTrimsWhitespaceFromSplitValues(): void
    {
        self::assertSame(['a', 'b', 'c'], CastUtility::array(' a , b , c '));
    }

    /** @test */
    public function arrayWrapsIntegerInArray(): void
    {
        self::assertSame([5], CastUtility::array(5));
    }

    /** @test */
    public function arrayReturnsEmptyArrayForNull(): void
    {
        self::assertSame([], CastUtility::array(null));
    }

    /** @test */
    public function arrayReturnsEmptyArrayForEmptyString(): void
    {
        self::assertSame([], CastUtility::array(''));
    }

    /** @test */
    public function arrayCallsToArrayOnObjectWithMethod(): void
    {
        $object = new class () {
            public function toArray(): array
            {
                return ['x', 'y'];
            }
        };

        self::assertSame(['x', 'y'], CastUtility::array($object));
    }

    /** @test */
    public function arrayThrowsTypeExceptionForObjectWithoutToArray(): void
    {
        $this->expectException(TypeException::class);
        CastUtility::array(new \stdClass());
    }

    // ---------------------------------------------------------------------------
    // bool()
    // ---------------------------------------------------------------------------

    /** @test */
    public function boolCastsTrueValue(): void
    {
        self::assertTrue(CastUtility::bool(true));
    }

    /** @test */
    public function boolCastsFalseValue(): void
    {
        self::assertFalse(CastUtility::bool(false));
    }

    /** @test */
    public function boolCastsOneToTrue(): void
    {
        self::assertTrue(CastUtility::bool(1));
    }

    /** @test */
    public function boolCastsZeroToFalse(): void
    {
        self::assertFalse(CastUtility::bool(0));
    }

    /** @test */
    public function boolThrowsTypeExceptionForArray(): void
    {
        $this->expectException(TypeException::class);
        CastUtility::bool([1]);
    }

    /** @test */
    public function boolThrowsTypeExceptionForObject(): void
    {
        $this->expectException(TypeException::class);
        CastUtility::bool(new \stdClass());
    }
}
