<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use ReflectionClass;
use ReflectionException;
use Zeroseven\Rampage\Domain\Model\Demand\ObjectDemand;

class PageObjectRegistration
{
    protected ?string $objectClassName;
    protected ?string $repositoryClassName;
    protected ?string $controllerClassName;
    protected ?string $demandClassName;
    protected ?string $title;
    protected ?string $iconIdentifier;
    protected bool $enabled = false;

    public function __construct(string $objectClassName = null, string $repositoryClassName = null, string $controllerClassName = null, string $demandClassName = null)
    {
        $this->objectClassName = $objectClassName;
        $this->repositoryClassName = $repositoryClassName;
        $this->controllerClassName = $controllerClassName;
        $this->demandClassName = $demandClassName;
    }

    public function getObjectClassName(): ?string
    {
        return $this->objectClassName;
    }

    public function setObjectClassName(string $objectClassName): self
    {
        $this->objectClassName = $objectClassName;
        return $this;
    }

    public function getRepositoryClassName(): ?string
    {
        return $this->repositoryClassName;
    }

    public function setRepositoryClassName(string $repositoryClassName): self
    {
        $this->repositoryClassName = $repositoryClassName;
        return $this;
    }

    public function getControllerClassName(): ?string
    {
        return $this->controllerClassName;
    }

    public function setControllerClassName(string $controllerClassName): self
    {
        $this->controllerClassName = $controllerClassName;
        return $this;
    }

    public function getDemandClassName(): string
    {
        return $this->demandClassName ?? ObjectDemand::class;
    }

    public function setDemandClassName(string $demandClassName): self
    {
        $this->demandClassName = $demandClassName;
        return $this;
    }

    public function getTitle(): string
    {
        try {
            return $this->title ?? ($this->title = (new ReflectionClass($this->objectClassName))->getShortName());
        } catch (ReflectionException $e) {
            return '[NO_TITLE]';
        }
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getIconIdentifier(bool $hideInMenu = null): string
    {
        return ($this->iconIdentifier ?? 'apps-pagetree-page-content-from-page') . ($hideInMenu === true ? '-hideinmenu' : '');
    }

    public function setIconIdentifier(string $iconIdentifier): self
    {
        $this->iconIdentifier = $iconIdentifier;
        return $this;
    }

    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->objectClassName;
    }
}
