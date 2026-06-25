<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function intCastsIntegerValue(): void
    {
        self::assertSame(42, CastUtility::int(42));
    }

    #[Test]
    public function intCastsStringInteger(): void
    {
        self::assertSame(7, CastUtility::int('7'));
    }

    #[Test]
    public function intCastsZero(): void
    {
        self::assertSame(0, CastUtility::int(0));
    }

    #[Test]
    public function intCastsNullToZero(): void
    {
        self::assertSame(0, CastUtility::int(null));
    }

    #[Test]
    public function intCastsEmptyStringToZero(): void
    {
        self::assertSame(0, CastUtility::int(''));
    }

    #[Test]
    public function intThrowsTypeExceptionForNonNumericString(): void
    {
        $this->expectException(TypeException::class);
        CastUtility::int('not-a-number');
    }

    #[Test]
    public function intThrowsTypeExceptionForArray(): void
    {
        $this->expectException(TypeException::class);
        CastUtility::int([1, 2, 3]);
    }

    // ---------------------------------------------------------------------------
    // string()
    // ---------------------------------------------------------------------------
    #[Test]
    public function stringCastsStringValue(): void
    {
        self::assertSame('hello', CastUtility::string('hello'));
    }

    #[Test]
    public function stringCastsIntegerToString(): void
    {
        self::assertSame('42', CastUtility::string(42));
    }

    #[Test]
    public function stringCastsNullToEmptyString(): void
    {
        self::assertSame('', CastUtility::string(null));
    }

    #[Test]
    public function stringJoinsArrayWithComma(): void
    {
        self::assertSame('a,b,c', CastUtility::string(['a', 'b', 'c']));
    }

    // ---------------------------------------------------------------------------
    // array()
    // ---------------------------------------------------------------------------
    #[Test]
    public function arrayReturnsArrayAsIs(): void
    {
        $input = [1, 2, 3];
        self::assertSame($input, CastUtility::array($input));
    }

    #[Test]
    public function arraySplitsCommaSeparatedString(): void
    {
        self::assertSame(['a', 'b', 'c'], CastUtility::array('a,b,c'));
    }

    #[Test]
    public function arraySplitsStringWithCustomDelimiter(): void
    {
        self::assertSame(['tag1', 'tag2', 'tag3'], CastUtility::array('tag1,tag2,tag3', ','));
    }

    #[Test]
    public function arrayTrimsWhitespaceFromSplitValues(): void
    {
        self::assertSame(['a', 'b', 'c'], CastUtility::array(' a , b , c '));
    }

    #[Test]
    public function arrayWrapsIntegerInArray(): void
    {
        self::assertSame([5], CastUtility::array(5));
    }

    #[Test]
    public function arrayReturnsEmptyArrayForNull(): void
    {
        self::assertSame([], CastUtility::array(null));
    }

    #[Test]
    public function arrayReturnsEmptyArrayForEmptyString(): void
    {
        self::assertSame([], CastUtility::array(''));
    }

    #[Test]
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

    #[Test]
    public function arrayThrowsTypeExceptionForObjectWithoutToArray(): void
    {
        $this->expectException(TypeException::class);
        CastUtility::array(new \stdClass());
    }

    // ---------------------------------------------------------------------------
    // bool()
    // ---------------------------------------------------------------------------
    #[Test]
    public function boolCastsTrueValue(): void
    {
        self::assertTrue(CastUtility::bool(true));
    }

    #[Test]
    public function boolCastsFalseValue(): void
    {
        self::assertFalse(CastUtility::bool(false));
    }

    #[Test]
    public function boolCastsOneToTrue(): void
    {
        self::assertTrue(CastUtility::bool(1));
    }

    #[Test]
    public function boolCastsZeroToFalse(): void
    {
        self::assertFalse(CastUtility::bool(0));
    }

    #[Test]
    public function boolThrowsTypeExceptionForArray(): void
    {
        $this->expectException(TypeException::class);
        CastUtility::bool([1]);
    }

    #[Test]
    public function boolThrowsTypeExceptionForObject(): void
    {
        $this->expectException(TypeException::class);
        CastUtility::bool(new \stdClass());
    }
}
