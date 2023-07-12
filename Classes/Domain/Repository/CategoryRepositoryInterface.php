<?php

namespace Zeroseven\Pagebased\Domain\Repository;

use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function initializeDemand(): DemandInterface;
}
