<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

abstract class AbstractPluginRegistration
{
    protected ?string $type = null;
    protected ?string $title = null;
    protected ?string $description = null;
    protected ?string $iconIdentifier = null;

    public function __construct(string $title)
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

    public function getType(): string
    {
        return $this->type ?? 'undefined';
    }

    public function getCType(Registration $registration): string
    {
        return str_replace('_', '', $registration->getExtensionName()) . '_' . $this->getType();
    }
}
