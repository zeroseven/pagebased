<?php

namespace Zeroseven\Rampage\Domain\Repository;

use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function initializeDemand(): DemandInterface;
}
