<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Repository;

use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception as PersistenceException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;

abstract class AbstractPageRepository extends AbstractRepository implements RepositoryInterface
{
    protected $defaultOrderings = [
        'sorting' => QueryInterface::ORDER_ASCENDING
    ];

    public function initializeObject(): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /** @throws AspectNotFoundException | InvalidQueryException | PersistenceException */
    public function createDemandConstraints(DemandInterface $demand, QueryInterface $query): array
    {
        $constraints = parent::createDemandConstraints($demand, $query);

        // Hide what wants to be hidden
        $constraints[] = $query->equals('nav_hide', 0);

        // Add language constraints
        $constraints[] = $query->logicalOr(
            $query->equals('l18n_cfg', 0),
            $query->logicalAnd(
                $query->greaterThanOrEqual('l18n_cfg', 1),
                $query->greaterThanOrEqual($GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['languageField'], 1)
            ),
        );

        return $constraints;
    }
}
