<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Registration
{
    protected string $extensionName;
    protected PageObjectRegistration $object;
    protected PageObjectRegistration $category;
    protected PluginRegistration $listPlugin;
    protected PluginRegistration $filterPlugin;

    public function __construct(string $extensionName, string $objectClassName, string $controllerClassName, string $repositoryClassName)
    {
        $this->extensionName = $extensionName;
        $this->object = GeneralUtility::makeInstance(PageObjectRegistration::class, $objectClassName, $controllerClassName, $repositoryClassName);
        $this->category = GeneralUtility::makeInstance(PageObjectRegistration::class);
        $this->listPlugin = GeneralUtility::makeInstance(PluginRegistration::class, $this->object->getTitle());
        $this->filterPlugin = GeneralUtility::makeInstance(PluginRegistration::class);
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

    public function setObjectTitle(string $value): self
    {
        return $this;
    }

    public function setObjectIconIdentifier(string $value): self
    {
        return $this;
    }

    public function setCategoryTitle(string $value): self
    {
        return $this;
    }

    public function setCategoryIdentifier(string $value): self
    {
        return $this;
    }

    public function setListPluginTitle(string $value): self
    {
        return $this;
    }

    public function setListPluginDescription(string $value): self
    {
        return $this;
    }

    public function setListPluginIconIdentifier(string $value): self
    {
        return $this;
    }

    public function setListPluginEnableFilterPlugin(string $value): self
    {
        return $this;
    }

    public function setFilterPluginTitle(string $value): self
    {
        return $this;
    }

    public function setFilterPluginDescription(string $value): self
    {
        return $this;
    }

    public function setFilterPluginIconIdentifier(string $value): self
    {
        return $this;
    }

    public function setFilterPluginEnableFilterPlugin(string $value): self
    {
        return $this;
    }

    public function store(): void
    {
        RegistrationService::addRegistration($this);
    }
}
