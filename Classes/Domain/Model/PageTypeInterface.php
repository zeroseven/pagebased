<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model;

interface PageTypeInterface extends PageObjectInterface
{
    public static function getType(): int;
}
