<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Domain\Model\Demand\GenericDemand;
use Zeroseven\Rampage\Domain\Repository\RepositoryInterface;

abstract class AbstractEntityRegistration implements RegistrationPropertyInterface
{
    protected string $title;
    protected ?string $className = null;
    protected ?string $repositoryClassName = null;
    protected ?string $demandClassName = null;

    public function __construct(string $title, string $className = null)
    {
        $this->title = $title;
        $this->className = $className;
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

    public function getClassName(): string
    {
        return $this->className ?? '';
    }

    public function setClassName(string $className): self
    {
        $this->className = $className;
        return $this;
    }

    public function getRepositoryClassName(): string
    {
        return $this->repositoryClassName ?? '';
    }

    public function getRepositoryClass(): RepositoryInterface
    {
        return GeneralUtility::makeInstance(ObjectManager::class)->get($this->getRepositoryClassName());
    }

    public function setRepositoryClass(string $repositoryClassName): self
    {
        $this->repositoryClassName = $repositoryClassName;
        return $this;
    }

    public function getDemandClassName(): string
    {
        return $this->demandClassName ?? '';
    }

    public function getDemandClass(): DemandInterface
    {
        if ($className = $this->getDemandClassName()) {
            return GeneralUtility::makeInstance($className);
        }

        return GenericDemand::build($this->className);
    }

    public function setDemandClassName(string $demandClassName): self
    {
        $this->demandClassName = $demandClassName;
        return $this;
    }
}
