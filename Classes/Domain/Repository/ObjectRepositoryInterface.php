<?php

namespace Zeroseven\Rampage\Domain\Repository;

use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;

interface ObjectRepositoryInterface extends RepositoryInterface
{
    public function setOrdering(DemandInterface $demand = null): void;

    public function initializeDemand(): DemandInterface;
}
