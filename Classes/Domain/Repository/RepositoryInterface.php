<?php

namespace Zeroseven\Pagebased\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;

interface RepositoryInterface extends \TYPO3\CMS\Extbase\Persistence\RepositoryInterface
{
    public function createDemandConstraints(DemandInterface $demand, QueryInterface $query): array;

    public function findByDemand(DemandInterface $demand): ?QueryResultInterface;

    public function findAll(DemandInterface $demand = null): ?QueryResultInterface;

    public function findByUidList($uidList, DemandInterface $demand = null): ?QueryResultInterface;

    public function getDefaultQuerySettings(): QuerySettingsInterface;
}
