<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

class PluginRegistration
{
    public const TYPE_LIST = 'list';
    public const TYPE_FILTER = 'filter';

    protected string $type;
    protected ?string $title;
    protected ?string $description;
    protected ?string $iconIdentifier;

    public function __construct(string $type, string $title = null, string $description = null, string $iconIdentifier = null)
    {
        $this->type = $type;
        $this->title = $title;
        $this->description = $description;
        $this->iconIdentifier = $iconIdentifier;
    }

    public function getType(): string
    {
        return $this->type;
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

    public function isEnabled(): bool
    {
        return $this->title !== null;
    }
}
