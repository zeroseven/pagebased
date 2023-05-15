<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Topic extends AbstractEntity
{
    protected ?string $object = null;
    protected ?string $title = null;

    public function getObject(): string
    {
        return $this->object ?? '';
    }

    public function setObject(string $object): self
    {
        $this->object = $object;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
