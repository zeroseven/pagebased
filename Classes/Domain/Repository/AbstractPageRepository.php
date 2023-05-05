<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Repository;

use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception as PersistenceException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Utility\IdentifierUtility;
use Zeroseven\Rampage\Utility\RootLineUtility;

abstract class AbstractPageRepository extends AbstractRepository implements RepositoryInterface
{
    public function initializeObject(): void
    {
        $querySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /** @throws AspectNotFoundException | InvalidQueryException | PersistenceException */
    public function createDemandConstraints(DemandInterface $demand, QueryInterface $query): array
    {
        $constraints = parent::createDemandConstraints($demand, $query);

        // Stay in the hood
        if ($startPageId = RootLineUtility::getRootPage()) {
            $constraints[] = $query->equals(IdentifierUtility::SITE_FIELD_NAME, $startPageId);
        }

        // Hide what wants to be hidden
        $constraints[] = $query->equals('nav_hide', 0);

        // Add language constraints
        $constraints[] = $query->logicalOr([
            $query->equals('l18n_cfg', 0),
            $query->logicalAnd([
                $query->greaterThanOrEqual('l18n_cfg', 1),
                $query->greaterThanOrEqual('sys_language_uid', 1)
            ]),
        ]);

        return $constraints;
    }
}
