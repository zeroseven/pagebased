<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Model;

interface CategoryInterface extends PageEntityInterface
{
    public function getRedirectCategory(): bool;

    public function setRedirectCategory($redirectCategory): self;
}
