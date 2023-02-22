<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class RootLineUtility
{
    protected static function getRequest(): ?ServerRequestInterface
    {
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface ? $GLOBALS['TYPO3_REQUEST'] : null;
    }

    protected static function isFrontendMode(): bool
    {
        return ($request = self::getRequest()) && ApplicationType::fromRequest($request)->isFrontend();
    }

    protected static function isBackendMode(): bool
    {
        return !self::isFrontendMode();
    }

    protected static function getCurrentPage(): int
    {
        if (($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController) {
            return (int)$GLOBALS['TSFE']->id;
        }

        if ($id = GeneralUtility::_GP('id')) {
            return (int)$id;
        }

        return 0;
    }

    protected static function getRootLine(int $startingPoint = null): array
    {
        if ($startingPoint === null && ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController && $rootLine = $GLOBALS['TSFE']->rootLine) {
            return $rootLine;
        }

        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\RootlineUtility::class, $startingPoint ?: self::getCurrentPage())->get();
    }

    public static function findDocumentType(int $documentType, int $startingPoint = null, array $rootLine = null): ?int
    {
        if (empty($rootLine)) {
            $rootLine = self::getRootLine($startingPoint);
        }

        foreach ($rootLine ?? [] as $row) {
            if (isset($row['doktype'], $row['uid']) && (int)$row['doktype'] === $documentType) {
                return (int)$row['uid'];
            }
        }

        return null;
    }

    public static function getRootPage(int $startingPoint = null): int
    {
        if (self::isBackendMode()) {
            foreach (GeneralUtility::makeInstance(BackendUtility::class)->BEgetRootLine($startingPoint ?: self::getCurrentPage()) ?: [] as $page) {
                if (($page['is_siteroot'] ?? false) || (int)($page['pid'] ?? 0) === 0) {
                    return (int)$page['uid'];
                }
            }
        } else {
            try {
                $site = $startingPoint === null && ($request = self::getRequest()) ? $request->getAttribute('site') : GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($startingPoint);
            } catch (SiteNotFoundException $e) {
                return 0;
            }

            return $site->getRootPageId();
        }

        return 0;
    }

    public static function findPagesBelow(int $startingPoint = null): array
    {
        return GeneralUtility::intExplode(',', GeneralUtility::makeInstance(QueryGenerator::class)->getTreeList($startingPoint ?: self::getCurrentPage(), 99));
    }
}
