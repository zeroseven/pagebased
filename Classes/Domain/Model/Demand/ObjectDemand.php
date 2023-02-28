<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model\Demand;

class ObjectDemand extends AbstractDemand
{
    protected function initProperties(): void
    {
        $this->addProperty('category', DemandProperty::TYPE_INTEGER);

        parent::initProperties();
    }
}
