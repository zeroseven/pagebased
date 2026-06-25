<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration;

use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Pagebased\Exception\RegistrationException;
use Zeroseven\Pagebased\Registration\Event\AfterStoreRegistrationEvent;
use Zeroseven\Pagebased\Registration\Event\BeforeStoreRegistrationEvent;

final class Registration
{
    private ?ObjectRegistration $objectRegistration = null;

    private ?CategoryRegistration $categoryRegistration = null;

    private ?ListPluginRegistration $listPluginRegistration = null;

    private ?FilterPluginRegistration $filterPluginRegistration = null;

    public function __construct(private readonly string $extensionName, private ?string $identifier = null) {}

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    public function getIdentifier(): string
    {
        return $this->identifier ?? $this->extensionName;
    }

    public function setIdentifier(string $identifier = null): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getObject(): ObjectRegistration
    {
        return $this->objectRegistration;
    }

    public function setObject(ObjectRegistration $objectRegistration): self
    {
        $this->objectRegistration = $objectRegistration;

        return $this;
    }

    public function getCategory(): CategoryRegistration
    {
        return $this->categoryRegistration;
    }

    public function setCategory(CategoryRegistration $categoryRegistration): self
    {
        $this->categoryRegistration = $categoryRegistration;

        return $this;
    }

    public function getListPlugin(): ?ListPluginRegistration
    {
        return $this->listPluginRegistration;
    }

    public function enableListPlugin(ListPluginRegistration $listPluginRegistration): self
    {
        $this->listPluginRegistration = $listPluginRegistration;

        return $this;
    }

    public function hasListPlugin(): bool
    {
        return $this->listPluginRegistration instanceof ListPluginRegistration;
    }

    public function getFilterPlugin(): ?FilterPluginRegistration
    {
        return $this->filterPluginRegistration;
    }

    public function enableFilterPlugin(FilterPluginRegistration $filterPluginRegistration): self
    {
        $this->filterPluginRegistration = $filterPluginRegistration;

        return $this;
    }

    public function hasFilterPlugin(): bool
    {
        return $this->filterPluginRegistration instanceof FilterPluginRegistration;
    }

    /** @throws RegistrationException */
    public function store(): void
    {
        if (!$this->objectRegistration instanceof ObjectRegistration) {
            throw new RegistrationException(sprintf('An object must be configured in extension "%s". Please call "setObject()" methode, contains instance of "%s"', $this->extensionName, ObjectRegistration::class), 1684312103);
        }

        if (!$this->categoryRegistration instanceof CategoryRegistration) {
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
