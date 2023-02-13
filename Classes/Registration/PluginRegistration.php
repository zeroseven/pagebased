<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

class PluginRegistration
{
    protected ?string $title;
    protected ?string $description;
    protected ?string $iconIdentifier;

    public function __construct(string $title = null)
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getIconIdentifier(): string
    {
        return $this->iconIdentifier ?? 'content-text';
    }

    public function setIconIdentifier(string $iconIdentifier): self
    {
        $this->iconIdentifier = $iconIdentifier;
        return $this;
    }
}
