<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration;

use ReflectionClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;
use Zeroseven\Pagebased\Domain\Model\Demand\GenericDemand;
use Zeroseven\Pagebased\Domain\Repository\RepositoryInterface;

abstract class AbstractRegistrationEntityProperty extends AbstractRegistration
{
    protected ?string $className = null;
    protected ?string $repositoryClassName = null;
    protected ?string $demandClassName = null;
    protected ?string $name = null;

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
        return $this->demandClassName ?? GenericDemand::class;
    }

    public function getDemandClass(): DemandInterface
    {
        return ($demandClass = $this->getDemandClassName()) === GenericDemand::class
            ? GenericDemand::build($this->className)
            : GeneralUtility::makeInstance($demandClass);
    }

    public function setDemandClassName(string $demandClassName): self
    {
        $this->demandClassName = $demandClassName;
        return $this;
    }

    public function getName(): string
    {
        return $this->name ?? ($this->name = $this->name = GeneralUtility::makeInstance(ReflectionClass::class, $this->className)->getShortName());
    }
}
