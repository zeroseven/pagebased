<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\Event\StoreRegistrationEvent;

class Registration
{
    protected string $extensionName;
    protected ?ObjectRegistration $object = null;
    protected ?CategoryRegistration $category = null;
    protected ?ListPluginRegistration $listPlugin = null;
    protected ?FilterPluginRegistration $filterPlugin = null;

    public function __construct(string $extensionName)
    {
        $this->extensionName = $extensionName;
    }

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    /** @throws RegistrationException */
    public function getObject(): ObjectRegistration
    {
        if ($this->object === null) {
            throw new RegistrationException('The object can not be empty', 1678709111);
        }

        return $this->object;
    }

    public function setObject(ObjectRegistration $objectRegistration): self
    {
        $this->object = $objectRegistration;

        return $this;
    }

    public function hasObject(): bool
    {
        return $this->object !== null;
    }

    public function getCategory(): ?CategoryRegistration
    {
        return $this->category;
    }

    public function enableCategory(CategoryRegistration $categoryRegistration): self
    {
        $this->category = $categoryRegistration;

        return $this;
    }

    public function hasCategory(): bool
    {
        return $this->category !== null;
    }

    public function getListPlugin(): ?ListPluginRegistration
    {
        return $this->listPlugin;
    }

    public function enableListPlugin(ListPluginRegistration $listPluginRegistration): self
    {
        $this->listPlugin = $listPluginRegistration;

        return $this;
    }

    public function hasListPlugin(): bool
    {
        return $this->listPlugin !== null;
    }

    public function getFilterPlugin(): ?FilterPluginRegistration
    {
        return $this->filterPlugin;
    }

    public function enableFilterPlugin(FilterPluginRegistration $filterPluginRegistration): self
    {
        $this->filterPlugin = $filterPluginRegistration;

        return $this;
    }

    public function hasFilterPlugin(): bool
    {
        return $this->filterPlugin !== null;
    }

    public function store(): void
    {
        $registration = GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new StoreRegistrationEvent($this))->getRegistration();

        RegistrationService::addRegistration($registration);
    }
}
