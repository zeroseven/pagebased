<?php

namespace Zeroseven\Pagebased\Domain\Repository;

use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;

interface ObjectRepositoryInterface extends RepositoryInterface
{
    public function setOrdering(DemandInterface $demand = null): void;

    public function initializeDemand(): DemandInterface;
}
