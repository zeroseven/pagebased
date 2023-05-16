<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model\Demand;

use Zeroseven\Rampage\Exception\PropertyException;
use Zeroseven\Rampage\Exception\TypeException;

/**
 * @method setCategory(mixed $value): self
 * @method getCategory(): int
 * @method setTags(mixed $value): self
 * @method getTags(): array
 */
class AbstractObjectDemand extends AbstractDemand implements ObjectDemandInterface
{
    public const PARAMETER_CONTENT_ID = '_c';
    public const PARAMETER_TOP_MODE = 'topMode';
    public const PARAMETER_EXCLUDE_CHILD_OBJECTS = 'excludeChildObjects';

    public const TOP_MODE_ONLY = 1;
    public const TOP_MODE_FIRST = 2;

    protected function initProperties(): void
    {
        parent::initProperties();

        $this->addProperty(self::PARAMETER_CONTENT_ID, DemandProperty::TYPE_INTEGER);
        $this->addProperty(self::PARAMETER_TOP_MODE, DemandProperty::TYPE_INTEGER);
        $this->addProperty(self::PARAMETER_EXCLUDE_CHILD_OBJECTS, DemandProperty::TYPE_BOOLEAN);

        $this->addProperty('category', DemandProperty::TYPE_INTEGER);
        $this->addProperty('tags', DemandProperty::TYPE_ARRAY, 'tagsString');
    }

    /** @throws PropertyException */
    public function getContentId(): int
    {
        return $this->getProperty(self::PARAMETER_CONTENT_ID)->getValue();
    }

    /** @throws TypeException | PropertyException */
    public function setContentId(mixed $value): self
    {
        $this->setProperty(self::PARAMETER_CONTENT_ID, $value);

        return $this;
    }

    /** @throws PropertyException */
    public function getTopObjectOnly(): bool
    {
        return $this->getProperty(self::PARAMETER_TOP_MODE)->getValue() === self::TOP_MODE_ONLY;
    }

    /** @throws PropertyException */
    public function getTopObjectFirst(): bool
    {
        return $this->getProperty(self::PARAMETER_TOP_MODE)->getValue() === self::TOP_MODE_FIRST;
    }

    /** @throws TypeException | PropertyException */
    public function setTop(mixed $value): self
    {
        $this->setProperty(self::PARAMETER_TOP_MODE, $value);

        return $this;
    }

    /** @throws TypeException | PropertyException */
    public function setExcludeChildObjects(mixed $value): ObjectDemandInterface
    {
        $this->setProperty(self::PARAMETER_EXCLUDE_CHILD_OBJECTS, $value);

        return $this;
    }

    /** @throws PropertyException */
    public function getExcludeChildObjects(): bool
    {
        return $this->getProperty(self::PARAMETER_EXCLUDE_CHILD_OBJECTS)->getValue();
    }
}
