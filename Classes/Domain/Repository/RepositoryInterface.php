<?php

namespace Zeroseven\Rampage\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;

interface RepositoryInterface extends \TYPO3\CMS\Extbase\Persistence\RepositoryInterface
{
    public function findByDemand(DemandInterface $demand): ?QueryResultInterface;

    public function findAll(DemandInterface $demand = null): ?QueryResultInterface;

    public function findByUidList($uidList, DemandInterface $demand = null): ?QueryResultInterface;

    public function getDefaultQuerySettings(): QuerySettingsInterface;
}
