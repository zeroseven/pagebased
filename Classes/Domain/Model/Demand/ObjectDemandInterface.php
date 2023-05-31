<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model\Demand;

interface ObjectDemandInterface extends DemandInterface
{
    public const PROPERTY_CONTENT_ID = '_c';
    public const PROPERTY_TOP_MODE = '_top_mode';
    public const PROPERTY_EXCLUDE_CHILD_OBJECTS = '_exclude_child_objects';

    public function getContentId(): int;

    public function setContentId(mixed $value): self;

    public function getTopObjectOnly(): bool;

    public function getTopObjectFirst(): bool;

    public function setExcludeChildObjects(mixed $value): self;

    public function getExcludeChildObjects(): bool;
}
