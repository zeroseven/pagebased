<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use ReflectionClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\AbstractPluginRegistration;
use Zeroseven\Rampage\Registration\Event\StoreRegistrationEvent;
use Zeroseven\Rampage\Registration\Registration;

class RegisterPluginEvent
{
    protected ?Registration $registration;

    /** @throws RegistrationException */
    protected function registerPlugin(?AbstractPluginRegistration $plugin): void
    {
        if ($plugin) {
            $controllerClassName = $this->registration->getObject()->getControllerClassName();
            $uncachedAction = $plugin->getType() . 'Uncached';

            if (GeneralUtility::makeInstance(ReflectionClass::class, $controllerClassName)->hasMethod($uncachedAction)) {
                $controllerActions = [$controllerClassName => $plugin->getType(), $uncachedAction];
                $nonCacheableControllerActions = [$controllerClassName => $uncachedAction];
            } else {
                $controllerActions = [$controllerClassName => $plugin->getType()];
                $nonCacheableControllerActions = [];
            }

            ExtensionUtility::configurePlugin($this->registration->getExtensionName(), ucfirst($plugin->getType()), $controllerActions, $nonCacheableControllerActions, ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT);
        }
    }

    /** @throws RegistrationException */
    public function __invoke(StoreRegistrationEvent $event)
    {
        if ($this->registration = $event->getRegistration()) {
            $this->registerPlugin($this->registration->getListPlugin());
            $this->registerPlugin($this->registration->getFilterPlugin());
        }
    }
}
