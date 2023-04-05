<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model;

interface PageTypeInterface extends PageEntityInterface
{
    public static function getType(): int;
}
