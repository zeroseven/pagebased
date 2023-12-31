<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Model;

abstract class AbstractCategory extends AbstractPage implements CategoryInterface
{
    protected bool $redirectCategory = false;

    public function getRedirectCategory(): bool
    {
        return $this->redirectCategory;
    }

    public function setRedirectCategory($redirectCategory): self
    {
        $this->redirectCategory = $redirectCategory;
        return $this;
    }
}
