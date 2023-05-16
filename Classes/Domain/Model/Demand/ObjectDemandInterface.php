<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model\Demand;

interface ObjectDemandInterface extends DemandInterface
{
    public const PARAMETER_CONTENT_ID = '_c';
    public const PARAMETER_TOP_MODE = 'topMode';
    public const PARAMETER_EXCLUDE_CHILD_OBJECTS = 'excludeChildObjects';

    public function getContentId(): int;

    public function setContentId(mixed $value): self;

    public function getTopObjectOnly(): bool;

    public function getTopObjectFirst(): bool;

    public function setExcludeChildObjects(mixed $value): self;

    public function getExcludeChildObjects(): bool;
}
