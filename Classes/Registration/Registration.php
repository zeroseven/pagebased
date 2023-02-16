<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Registration\Event\StoreRegistrationEvent;

class Registration
{
    protected string $extensionName;
    protected PageObjectRegistration $object;
    protected PageObjectRegistration $category;
    protected PluginRegistration $listPlugin;
    protected PluginRegistration $filterPlugin;

    public function __construct(string $extensionName, string $objectClassName, string $repositoryClassName, string $controllerClassName)
    {
        $this->extensionName = $extensionName;
        $this->object = GeneralUtility::makeInstance(PageObjectRegistration::class, $objectClassName, $repositoryClassName, $controllerClassName);
        $this->category = GeneralUtility::makeInstance(PageObjectRegistration::class);
        $this->listPlugin = GeneralUtility::makeInstance(PluginRegistration::class, PluginRegistration::TYPE_LIST, $this->object->getTitle());
        $this->filterPlugin = GeneralUtility::makeInstance(PluginRegistration::class, PluginRegistration::TYPE_FILTER);
    }

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    public function getObject(): PageObjectRegistration
    {
        return $this->object;
    }

    public function getCategory(): PageObjectRegistration
    {
        return $this->category;
    }

    public function getListPlugin(): PluginRegistration
    {
        return $this->listPlugin;
    }

    public function getFilterPlugin(): PluginRegistration
    {
        return $this->filterPlugin;
    }

    public function addCategory(string $objectClassName, string $repositoryClassName, string $controllerClassName = null): self
    {
        $this->category = GeneralUtility::makeInstance(PageObjectRegistration::class, $objectClassName, $repositoryClassName, $controllerClassName);

        return $this;
    }

    public function addListPlugin(string $title, string $description = null, string $iconIdentifier = null): self
    {
        $this->listPlugin = GeneralUtility::makeInstance(PluginRegistration::class, PluginRegistration::TYPE_LIST, $title, $description, $iconIdentifier);

        return $this;
    }

    public function addFilterPlugin(string $title, string $description = null, string $iconIdentifier = null): self
    {
        $this->filterPlugin = GeneralUtility::makeInstance(PluginRegistration::class, PluginRegistration::TYPE_FILTER, $title, $description, $iconIdentifier);

        return $this;
    }

    public function setObjectTitle(string $title): self
    {
        $this->object->setTitle($title);

        return $this;
    }

    public function setObjectIconIdentifier(string $iconIdentifier): self
    {
        $this->object->setIconIdentifier($iconIdentifier);

        return $this;
    }

    public function setCategoryTitle(string $title): self
    {
        $this->category->setTitle($title);

        return $this;
    }

    public function setCategoryIconIdentifier(string $iconIdentifier): self
    {
        $this->category->setIconIdentifier($iconIdentifier);

        return $this;
    }

    public function setListPluginTitle(string $title): self
    {
        $this->listPlugin->setTitle($title);

        return $this;
    }

    public function setListPluginDescription(string $description): self
    {
        $this->listPlugin->setDescription($description);

        return $this;
    }

    public function setListPluginIconIdentifier(string $iconIdentifier): self
    {
        $this->listPlugin->setIconIdentifier($iconIdentifier);

        return $this;
    }

    public function setFilterPluginTitle(string $title): self
    {
        $this->filterPlugin->setTitle($title);

        return $this;
    }

    public function setFilterPluginDescription(string $description): self
    {
        $this->filterPlugin->setDescription($description);

        return $this;
    }

    public function setFilterPluginIconIdentifier(string $iconIdentifier): self
    {
        $this->filterPlugin->setIconIdentifier($iconIdentifier);

        return $this;
    }

    public function store(): void
    {
        $registration = GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new StoreRegistrationEvent($this))->getRegistration();

        RegistrationService::addRegistration($registration);
    }
}
