<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model\Demand;

/**
 * @method setCategory(mixed $value): self
 * @method getCategory(): int
 * @method setTags(mixed $value): self
 * @method getTags(): array
 */
class ObjectDemand extends AbstractDemand
{
    protected function initProperties(): void
    {
        $this->addProperty('category', DemandProperty::TYPE_INTEGER);
        $this->addProperty('tags', DemandProperty::TYPE_ARRAY);

        parent::initProperties();
    }
}
