<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Registration\Registration;

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

        if (($edit = $_GET['edit'][AbstractPage::TABLE_NAME] ?? null) && $id = array_key_first($edit)) {
            return (int)$id;
        }

        if (($edit = $_GET['edit'] ?? null) && ($table = array_key_first($edit)) && $uid = array_key_first($edit[$table])) {
            $row = BackendUtility::getRecord($table, $uid, 'pid');

            return $row['pid'] ?? 0;
        }

        return 0;
    }

    public static function getParentPage(int $staringPoint = null): int
    {
        $staringPoint || $staringPoint = self::getCurrentPage();

        $list = [];
        try {
            self::lookUp($list, $staringPoint, 0, 1, self::getTreeCollectQueryBuilder());
        } catch (DBALException | DriverException $e) {
        }

        if (($parentPage = reset($list)) && $uid = $parentPage['uid'] ?? null) {
            return $uid;
        }

        return 0;
    }

    protected static function getRootLine(int $startingPoint = null): array
    {
        if ($startingPoint === null && ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController && $rootLine = $GLOBALS['TSFE']->rootLine) {
            return $rootLine;
        }

        return self::collectPagesBelow($startingPoint);
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
                if (!empty($pagesAbove = self::collectPagesAbove($startingPoint))) {
                    return end($pagesAbove)['uid'] ?? 0;
                }

                return 0;
            }

            return $site->getRootPageId();
        }

        return 0;
    }

    protected static function getTreeCollectQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select('*')->from('pages')
            ->andWhere($queryBuilder->expr()->eq('sys_language_uid', 0))
            ->orderBy($GLOBALS['TCA']['pages']['ctrl']['sortby'] ?? 'uid');

        return $queryBuilder;
    }

    /** @throws DBALException | DriverException */
    protected static function getStartingPoint(array &$list, int $startingPoint, QueryBuilder $queryBuilder): void
    {
        $queryBuilder->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($startingPoint, Connection::PARAM_INT)))
            ->orWhere($queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($startingPoint, Connection::PARAM_INT)));

        foreach ($queryBuilder->execute()->fetchAllAssociative() as $row) {
            if ($uid = (int)($row['uid'] ?? 0)) {
                $list[$uid] = $row;
            }
        }
    }

    /** @throws DBALException | DriverException */
    protected static function lookUp(array &$list, int $pid, int $looped, int $depth, QueryBuilder $queryBuilder): void
    {
        if ($pid > 0 && $looped <= $depth) {
            $queryBuilder->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)));

            $statement = $queryBuilder->execute();
            while ($row = $statement->fetchAssociative()) {
                if ($uid = (int)($row['uid'] ?? 0)) {
                    if ($looped) {
                        $list[$uid] = $row;
                    }

                    if ($pid = (int)($row['pid'] ?? 0)) {
                        self::lookUp($list, $pid, $looped + 1, $depth, $queryBuilder);
                    }
                }
            }
        }
    }

    /** @throws DBALException | DriverException */
    protected static function lookDown(array &$list, int $uid, int $looped, int $depth, QueryBuilder $queryBuilder): void
    {
        if ($looped < $depth) {
            $queryBuilder->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)));

            $statement = $queryBuilder->execute();
            while ($row = $statement->fetchAssociative()) {
                if ($uid = (int)($row['uid'] ?? 0)) {
                    $list[$uid] = $row;

                    self::lookDown($list, $uid, $looped + 1, $depth, $queryBuilder);
                }
            }
        }
    }

    /** @throws AspectNotFoundException | DriverException | DBALException */
    protected static function searchContentElementInRootline(int $pid, string $pluginName, QueryBuilder $queryBuilder, array $constraints = null): ?array
    {
        if ($pid > 0) {
            if ($constraints === null) {
                $constraints = [$queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter($pluginName, Connection::PARAM_STR))];
                $languageId = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id', 0);

                if ($languageField = $GLOBALS['TCA']['tt_content']['ctrl']['languageField'] ?? null) {
                    $constraints[] = $queryBuilder->expr()->in($languageField, [-1, $languageId]);
                }

                if ($hiddenField = $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['disabled'] ?? null) {
                    $constraints[] = $queryBuilder->expr()->eq($hiddenField, 0);
                }
            }

            $queryBuilder->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)));
            $queryBuilder->andWhere(...$constraints);

            // An element with the same CType can be found on the given pid
            if (count($result = $queryBuilder->execute()->fetchAllAssociative())) {
                return $result[0];
            }

            // Check the next level in rootline
            if ($nextPid = self::getParentPage($pid)) {
                return self::searchContentElementInRootline($nextPid, $pluginName, $queryBuilder, $constraints);
            }
        }

        return null;
    }

    public static function collectPagesAbove(?int $startingPoint = null, ?bool $includingStartingPoint = null, ?int $depth = null): array
    {
        $startingPoint || $startingPoint = self::getCurrentPage();
        $list = [];
        $queryBuilder = self::getTreeCollectQueryBuilder();

        try {
            if ($includingStartingPoint) {
                self::getStartingPoint($list, $startingPoint, $queryBuilder);
            }

            self::lookUp($list, $startingPoint, 0, $depth ?? 100, $queryBuilder);
        } catch (DBALException | DriverException $e) {
        }

        return $list;
    }

    public static function collectPagesBelow(?int $startingPoint = null, ?bool $includingStartingPoint = null, ?int $depth = null): array
    {
        $startingPoint || $startingPoint = self::getCurrentPage();
        $list = [];
        $queryBuilder = self::getTreeCollectQueryBuilder();

        try {
            if ($includingStartingPoint) {
                self::getStartingPoint($list, $startingPoint, $queryBuilder);
            }

            self::lookDown($list, $startingPoint, 0, $depth ?? 100, $queryBuilder);
        } catch (DBALException | DriverException $e) {
        }

        return $list;
    }

    public static function findListPlugin(Registration $registration, ?int $startingPoint = null, ?bool $includingStartingPoint = null): ?array
    {
        if ($registration->hasListPlugin()) {
            $startingPoint || $startingPoint = self::getCurrentPage();
            $cType = $registration->getListPlugin()->getCType($registration);

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder->select('*')->from('tt_content')
                ->orderBy($GLOBALS['TCA']['pages']['ctrl']['sortby'] ?? 'uid');

            if ($includingStartingPoint !== true && ($parentPageUid = self::getParentPage($startingPoint))) {
                $startingPoint = $parentPageUid;
            }

            try {
                return self::searchContentElementInRootline($startingPoint, $cType, $queryBuilder);
            } catch (DBALException | DriverException | AspectNotFoundException $e) {
            }
        }

        return null;
    }
}
