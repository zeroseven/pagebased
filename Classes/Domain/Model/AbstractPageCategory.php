<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model;

abstract class AbstractPageCategory extends AbstractPage implements PageTypeInterface
{
    protected bool $redirect;

    public function getRedirect(): int
    {
        return (int)($this->redirect ?? 0);
    }

    public function hasRedirect(): bool
    {
        return (bool)$this->getRedirect();
    }

    public function setRedirect($redirect): self
    {
        $this->redirect = $redirect;
        return $this;
    }
}
