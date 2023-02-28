<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Repository;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Utility\CastUtility;
use Zeroseven\Rampage\Utility\RootLineUtility;

abstract class AbstractPageRepository extends AbstractRepository implements RepositoryInterface
{
    public function initializeObject(): void
    {
        $querySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /** @throws AspectNotFoundException | InvalidQueryException */
    public function getRootlineAndLanguageConstraints(DemandInterface $demand, QueryInterface $query): array
    {
        // Build array
        $constraints = [];

        // Stay in the hood
        if (empty($demand->getUidList()) && $startPageId = RootLineUtility::getRootPage()) {
            $treeTableField = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id', null) ? 'pid' : 'uid';
            $constraints[] = $query->in($treeTableField, array_keys(RootLineUtility::collectPagesBelow($startPageId)));
        }

        // Hide what wants to be hidden
        $constraints[] = $query->equals('nav_hide', 0);

        // Add language constraints
        $constraints[] = $query->logicalOr([
            $query->equals('l18n_cfg', 0),
            $query->logicalAnd([
                $query->greaterThanOrEqual('l18n_cfg', 1),
                $query->greaterThanOrEqual('sys_language_uid', 1),
            ]),
        ]);

        return $constraints;
    }

    /** @throws AspectNotFoundException | InvalidQueryException */
    protected function createDemandConstraints(DemandInterface $demand, QueryInterface $query): array
    {
        $constraints = parent::createDemandConstraints($demand, $query);

        return array_merge($constraints, $this->getRootlineAndLanguageConstraints($demand, $query));
    }

    public function findByUid(mixed $pageUid, bool $ignoreRestrictions = null): ?object
    {
        // Convert the uid to an integer
        $uid = CastUtility::int($pageUid);

        // Load page without restrictions
        if ($ignoreRestrictions) {
            $query = $this->createQuery();

            if ((int)GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id', 0) > 0) {
                $constraint = $query->equals('l10n_parent', $uid);
            } else {
                $constraint = $query->equals('uid', $uid);
            }

            $query->setLimit(1);
            $query->matching($constraint);

            // Allow hidden pages
            $query->getQuerySettings()->setIgnoreEnableFields(true)->setIncludeDeleted(true)->setRespectStoragePage(false);

            // Get pages and return the first one …
            return ($pages = $query->execute()) ? $pages->getFirst() : null;
        }

        return parent::findByUid($uid);
    }
}
