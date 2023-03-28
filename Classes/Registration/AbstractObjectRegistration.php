<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Zeroseven\Rampage\Domain\Model\PageTypeInterface;
use Zeroseven\Rampage\Domain\Repository\RepositoryInterface;
use Zeroseven\Rampage\Exception\RegistrationException;

abstract class AbstractObjectRegistration
{
    protected string $title;
    protected ?string $className = null;
    protected ?string $repositoryClassName = null;
    protected ?string $controllerClassName = null;
    protected ?string $iconIdentifier = null;

    public function __construct(string $title, string $className = null)
    {
        $this->title = $title;
        $this->className = $className;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function setClassName(string $className): self
    {
        $this->className = $className;
        return $this;
    }

    public function getRepositoryClassName(): ?string
    {
        return $this->repositoryClassName;
    }

    public function getRepositoryClass(): RepositoryInterface
    {
        return GeneralUtility::makeInstance(ObjectManager::class)->get($this->repositoryClassName);
    }

    public function setRepositoryClass(string $repositoryClassName): self
    {
        $this->repositoryClassName = $repositoryClassName;
        return $this;
    }

    public function getControllerClassName(): ?string
    {
        return $this->controllerClassName;
    }

    public function setControllerClass(string $controllerClassName): self
    {
        $this->controllerClassName = $controllerClassName;
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

    /** @throws RegistrationException */
    public function getObjectType(): int
    {
        if (!is_subclass_of($this->className, PageTypeInterface::class)) {
            throw new RegistrationException(sprintf('Object "%s" is not a subclass of %s', $this->className, PageTypeInterface::class), 1677876156);
        }

        return $this->className::getType();
    }
}