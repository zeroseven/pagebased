<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\EventListener;

use TYPO3\CMS\Core\Error\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Zeroseven\Pagebased\Registration\AbstractRegistrationPluginProperty;
use Zeroseven\Pagebased\Registration\Event\AfterStoreRegistrationEvent;
use Zeroseven\Pagebased\Registration\Registration;

class RegisterPluginEvent
{
    protected ?Registration $registration = null;

    protected function registerPlugin(?AbstractRegistrationPluginProperty $registrationPluginProperty): void
    {
        if ($registrationPluginProperty instanceof AbstractRegistrationPluginProperty) {
            $controllerClassName = $this->registration->getObject()->getControllerClassName();
            $uncachedAction = $registrationPluginProperty->getType() . 'Uncached';

            if (GeneralUtility::makeInstance(\ReflectionClass::class, $controllerClassName)->hasMethod($uncachedAction)) {
                $controllerActions = [$controllerClassName => $registrationPluginProperty->getType(), $uncachedAction];
                $nonCacheableControllerActions = [$controllerClassName => $uncachedAction];
            } else {
                $controllerActions = [$controllerClassName => $registrationPluginProperty->getType()];
                $nonCacheableControllerActions = [];
            }

            // Register as a content element (CType). Without the explicit type, configurePlugin
            // defaults to the legacy "list_type" plugin on TYPO3 v13 (rendering as
            // "tt_content.list.20.<sig>"), which never matches our CType-based content elements and
            // yields "no rendering definition" in the frontend. On v14 "CType" is the only allowed
            // value, so passing it works on both versions.
            ExtensionUtility::configurePlugin($this->registration->getExtensionName(), ucfirst($registrationPluginProperty->getType()), $controllerActions, $nonCacheableControllerActions, ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT);
        }
    }

    public function __invoke(AfterStoreRegistrationEvent $afterStoreRegistrationEvent): void
    {
        try {
            if ($this->registration = $afterStoreRegistrationEvent->getRegistration()) {
                $this->registerPlugin($this->registration->getListPlugin());
                $this->registerPlugin($this->registration->getFilterPlugin());
            }
        } catch (Exception) {
        }
    }
}
