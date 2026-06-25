<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Repository;

use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function initializeDemand(): DemandInterface;
}
