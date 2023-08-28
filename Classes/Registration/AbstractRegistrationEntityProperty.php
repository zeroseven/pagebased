<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration;

use ReflectionClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;
use Zeroseven\Pagebased\Domain\Model\Demand\GenericDemand;
use Zeroseven\Pagebased\Domain\Repository\RepositoryInterface;

abstract class AbstractRegistrationEntityProperty extends AbstractRegistration
{
    protected ?string $className = null;
    protected ?string $repositoryClassName = null;
    protected ?string $demandClassName = null;
    protected ?string $name = null;
    protected ?string $sortingField = null;
    protected ?bool $sortingDirectionAscending = null;

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
        return GeneralUtility::makeInstance($this->getRepositoryClassName());
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
        return $this->name ?? ($this->name = GeneralUtility::makeInstance(ReflectionClass::class, $this->className)->getShortName());
    }

    public function setSorting(string $field, bool $descending = null): self
    {
        $this->sortingField = $field;
        $this->sortingDirectionAscending = !$descending;

        return $this;
    }

    public function setSortingField(string $field): self
    {
        $this->sortingField = $field;
        return $this;
    }

    public function enableSortingDirectionAscending(): self
    {
        $this->sortingDirectionAscending = true;
        return $this;
    }

    public function disableSortingDirectionAscending(): self
    {
        $this->sortingDirectionAscending = false;
        return $this;
    }

    public function getSortingField(): string
    {
        return $this->sortingField ?? $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['sortby'];
    }

    public function isSortingAscending(): bool
    {
        return $this->sortingDirectionAscending ?? true;
    }

    public function isSortingDescending(): bool
    {
        return !$this->isSortingAscending();
    }
}
