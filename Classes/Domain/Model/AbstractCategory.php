<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model;

abstract class AbstractCategory extends AbstractPage implements PageTypeInterface
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
