<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Page\PageInformation;

/**
 * Facade around frontend request attributes that replace the removed
 * TypoScriptFrontendController / $GLOBALS['TSFE'] (dropped in TYPO3 v14).
 *
 * - Page information is read from the "frontend.page.information" request
 *   attribute (available since TYPO3 v13).
 * - Cache disabling uses the "frontend.cache.instruction" request attribute
 *   (TYPO3 v14+) and falls back to TypoScriptFrontendController::set_no_cache()
 *   on TYPO3 v13.
 */
class FrontendRequestUtility
{
    protected static function resolveRequest(?ServerRequestInterface $serverRequest): ?ServerRequestInterface
    {
        if ($serverRequest instanceof ServerRequestInterface) {
            return $serverRequest;
        }

        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface ? $GLOBALS['TYPO3_REQUEST'] : null;
    }

    protected static function getPageInformation(?ServerRequestInterface $serverRequest): ?PageInformation
    {
        $pageInformation = self::resolveRequest($serverRequest)?->getAttribute('frontend.page.information');

        return $pageInformation instanceof PageInformation ? $pageInformation : null;
    }

    public static function getPageId(?ServerRequestInterface $serverRequest = null): int
    {
        return self::getPageInformation($serverRequest)?->getId() ?? 0;
    }

    /** @return array<string, mixed>|null */
    public static function getPageRecord(?ServerRequestInterface $serverRequest = null): ?array
    {
        return self::getPageInformation($serverRequest)?->getPageRecord() ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function getRootLine(?ServerRequestInterface $serverRequest = null): array
    {
        return self::getPageInformation($serverRequest)?->getRootLine() ?? [];
    }

    public static function disableCache(?ServerRequestInterface $serverRequest = null, string $reason = 'Disabled by pagebased'): void
    {
        // TYPO3 v14+: use the frontend cache instruction request attribute.
        $cacheInstruction = self::resolveRequest($serverRequest)?->getAttribute('frontend.cache.instruction');
        if (is_object($cacheInstruction) && method_exists($cacheInstruction, 'disableCache')) {
            $cacheInstruction->disableCache($reason);

            return;
        }

        // TYPO3 v13: fall back to the (deprecated) TypoScriptFrontendController.
        $typoScriptFrontendController = $GLOBALS['TSFE'] ?? null;
        if (is_object($typoScriptFrontendController) && method_exists($typoScriptFrontendController, 'set_no_cache')) {
            $typoScriptFrontendController->set_no_cache();
        }
    }
}
