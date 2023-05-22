<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\Event\AfterStoreRegistrationEvent;
use Zeroseven\Rampage\Registration\Event\BeforeStoreRegistrationEvent;

final class Registration
{
    protected string $extensionName;
    protected ?string $identifier = null;
    protected ?ObjectRegistration $object = null;
    protected ?CategoryRegistration $category = null;
    protected ?ListPluginRegistration $listPlugin = null;
    protected ?FilterPluginRegistration $filterPlugin = null;

    public function __construct(string $extensionName, ?string $identifier = null)
    {
        $this->extensionName = $extensionName;
        $this->identifier = $identifier ?? $extensionName;
    }

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    public function getIdentifier(): string
    {
        return $this->identifier ?? $this->identifier = $this->extensionName . '_' . substr(md5($this->object->getClassName()), 0, 7);;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
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

    /** @throws RegistrationException */
    public function store(): void
    {
        if ($this->object === null) {
            throw new RegistrationException(sprintf('An object must be configured in extension "%s". Please call "setObject()" methode, contains instance of "%s"', $this->extensionName, ObjectRegistration::class), 1684312103);
        }

        if ($this->category === null) {
            throw new RegistrationException(sprintf('A category must be configured in extension "%s". Please call "setCategory()" methode, contains instance of "%s"', $this->extensionName, CategoryRegistration::class), 1684312124);
        }

        GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new BeforeStoreRegistrationEvent($this))->getRegistration();

        RegistrationService::addRegistration($this);

        GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new AfterStoreRegistrationEvent($this));
    }

    public static function create(...$arguments): self
    {
        return GeneralUtility::makeInstance(self::class, ...$arguments);
    }
}
