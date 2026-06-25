<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Unit\Utility;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Zeroseven\Pagebased\Utility\FrontendRequestUtility;

/**
 * Tests for FrontendRequestUtility.
 *
 * This utility replaces the removed TypoScriptFrontendController / $GLOBALS['TSFE']
 * (dropped in TYPO3 v14) by reading the request attributes "frontend.page.information"
 * and "frontend.cache.instruction". These tests pin that behaviour.
 */
class FrontendRequestUtilityTest extends UnitTestCase
{
    private function requestWithPageInformation(PageInformation $pageInformation): ServerRequest
    {
        return (new ServerRequest())->withAttribute('frontend.page.information', $pageInformation);
    }

    public function testGetPageIdReadsFromFrontendPageInformation(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setId(42);

        self::assertSame(42, FrontendRequestUtility::getPageId($this->requestWithPageInformation($pageInformation)));
    }

    public function testGetPageIdReturnsZeroWithoutPageInformation(): void
    {
        self::assertSame(0, FrontendRequestUtility::getPageId(new ServerRequest()));
    }

    public function testGetPageRecordReadsFromFrontendPageInformation(): void
    {
        $record = ['uid' => 42, 'title' => 'Example'];

        $pageInformation = new PageInformation();
        $pageInformation->setId(42);
        $pageInformation->setPageRecord($record);

        self::assertSame($record, FrontendRequestUtility::getPageRecord($this->requestWithPageInformation($pageInformation)));
    }

    public function testGetPageRecordReturnsNullWithoutPageInformation(): void
    {
        self::assertNull(FrontendRequestUtility::getPageRecord(new ServerRequest()));
    }

    public function testGetRootLineReadsFromFrontendPageInformation(): void
    {
        $rootLine = [['uid' => 42], ['uid' => 1]];

        $pageInformation = new PageInformation();
        $pageInformation->setRootLine($rootLine);

        self::assertSame($rootLine, FrontendRequestUtility::getRootLine($this->requestWithPageInformation($pageInformation)));
    }

    public function testGetRootLineReturnsEmptyArrayWithoutPageInformation(): void
    {
        self::assertSame([], FrontendRequestUtility::getRootLine(new ServerRequest()));
    }

    public function testDisableCacheUsesFrontendCacheInstruction(): void
    {
        $cacheInstruction = new CacheInstruction();
        self::assertTrue($cacheInstruction->isCachingAllowed(), 'Caching should be allowed before disabling');

        $serverRequest = (new ServerRequest())->withAttribute('frontend.cache.instruction', $cacheInstruction);
        FrontendRequestUtility::disableCache($serverRequest, 'test reason');

        self::assertFalse($cacheInstruction->isCachingAllowed(), 'Caching should be disabled via the cache instruction');
    }

    public function testDisableCacheDoesNotThrowWithoutAnyMechanism(): void
    {
        unset($GLOBALS['TSFE']);

        // No cache instruction attribute and no TSFE: must be a no-op, not a fatal error.
        FrontendRequestUtility::disableCache(new ServerRequest());

        $this->addToAssertionCount(1);
    }
}
