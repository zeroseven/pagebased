<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration;

abstract class AbstractRegistrationPluginProperty extends AbstractRegistration
{
    protected string $type;
    protected ?string $description = null;
    protected ?string $iconIdentifier = null;

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
        return $this->type;
    }

    public function getCType(Registration $registration): string
    {
        return str_replace('_', '', $registration->getExtensionName()) . '_' . $this->getType();
    }
}
