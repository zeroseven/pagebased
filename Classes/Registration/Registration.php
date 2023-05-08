<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Registration\Event\StoreRegistrationEvent;

class Registration
{
    protected string $extensionName;
    protected ?ObjectRegistration $object = null;
    protected ?CategoryRegistration $category = null;
    protected ?ListPluginRegistration $listPlugin = null;
    protected ?FilterPluginRegistration $filterPlugin = null;
    protected ?string $identifier = null;

    public function __construct(string $extensionName)
    {
        $this->extensionName = $extensionName;
    }

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    public function getObject(): ObjectRegistration
    {
        return $this->object;
    }

    public function setObject(ObjectRegistration $objectRegistration): self
    {
        $this->object = $objectRegistration;

        return $this;
    }

    public function getCategory(): CategoryRegistration
    {
        return $this->category;
    }

    public function setCategory(CategoryRegistration $categoryRegistration): self
    {
        $this->category = $categoryRegistration;

        return $this;
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

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function store(): void
    {
        $this->identifier = $this->extensionName . '_' . substr(md5($this->object->getClassName()), 0, 7);
        $registration = GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new StoreRegistrationEvent($this))->getRegistration();

        RegistrationService::addRegistration($registration);
    }
}
