<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Zeroseven\Rampage\Registration\Event\StoreRegistrationEvent;
use Zeroseven\Rampage\Registration\Registration;

class RegisterPluginEvent
{
    protected function registerListPlugin(Registration $registration): void
    {
        if (($listPlugin = $registration->getListPlugin()) && $registration->getListPlugin()->isEnabled()) {
            $controllerClassName = $registration->getObject()->getControllerClassName();
            $uncachedAction = $listPlugin->getType() . 'Uncached';

            if (GeneralUtility::makeInstance(\ReflectionClass::class, $controllerClassName)->hasMethod($uncachedAction)) {
                $controllerActions = [$controllerClassName => $listPlugin->getType(), $uncachedAction];
                $nonCacheableControllerActions = [$controllerClassName => $uncachedAction];
            } else {
                $controllerActions = [$controllerClassName => $listPlugin->getType()];
                $nonCacheableControllerActions = [];
            }

            ExtensionUtility::configurePlugin($registration->getExtensionName(), ucfirst($listPlugin->getType()), $controllerActions, $nonCacheableControllerActions, ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT);
        }
    }

    public function __invoke(StoreRegistrationEvent $event)
    {
        $registration = $event->getRegistration();

        $this->registerListPlugin($registration);
    }
}
