<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Model\Demand;

use Zeroseven\Pagebased\Exception\PropertyException;
use Zeroseven\Pagebased\Exception\TypeException;

/**
 * @method setCategory(mixed $value): self
 * @method getCategory(): int
 * @method setTags(mixed $value): self
 * @method getTags(): array
 */
class AbstractObjectDemand extends AbstractDemand implements ObjectDemandInterface
{
    public const TOP_MODE_ONLY = 1;
    public const TOP_MODE_FIRST = 2;

    protected function initProperties(): void
    {
        parent::initProperties();

        // @extensionScannerIgnoreLine
        $this->addProperty(self::PROPERTY_CONTENT_ID, DemandProperty::TYPE_INTEGER);
        // @extensionScannerIgnoreLine
        $this->addProperty(self::PROPERTY_TOP_MODE, DemandProperty::TYPE_INTEGER);
        // @extensionScannerIgnoreLine
        $this->addProperty(self::PROPERTY_INCLUDE_CHILD_OBJECTS, DemandProperty::TYPE_BOOLEAN);

        // @extensionScannerIgnoreLine
        $this->addProperty('category', DemandProperty::TYPE_INTEGER);
        // @extensionScannerIgnoreLine
        $this->addProperty('tags', DemandProperty::TYPE_ARRAY, 'tagsString');
    }

    /** @throws PropertyException */
    public function getContentId(): int
    {
        return $this->getProperty(self::PROPERTY_CONTENT_ID)->getValue();
    }

    /** @throws TypeException | PropertyException */
    public function setContentId(mixed $value): self
    {
        $this->setProperty(self::PROPERTY_CONTENT_ID, $value);

        return $this;
    }

    /** @throws PropertyException */
    public function getTopObjectOnly(): bool
    {
        return $this->getProperty(self::PROPERTY_TOP_MODE)->getValue() === self::TOP_MODE_ONLY;
    }

    /** @throws PropertyException */
    public function getTopObjectFirst(): bool
    {
        return $this->getProperty(self::PROPERTY_TOP_MODE)->getValue() === self::TOP_MODE_FIRST;
    }

    /** @throws TypeException | PropertyException */
    public function setTop(mixed $value): self
    {
        $this->setProperty(self::PROPERTY_TOP_MODE, $value);

        return $this;
    }

    /** @throws TypeException | PropertyException */
    public function setIncludeChildObjects(mixed $value): ObjectDemandInterface
    {
        $this->setProperty(self::PROPERTY_INCLUDE_CHILD_OBJECTS, $value);

        return $this;
    }

    /** @throws PropertyException */
    public function getIncludeChildObjects(): bool
    {
        return $this->getProperty(self::PROPERTY_INCLUDE_CHILD_OBJECTS)->getValue();
    }
}
