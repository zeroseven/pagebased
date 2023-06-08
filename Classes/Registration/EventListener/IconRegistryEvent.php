<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Imaging\IconProvider\AppIconProvider;
use Zeroseven\Rampage\Registration\Event\BeforeStoreRegistrationEvent;

class IconRegistryEvent
{
    public function __invoke(BeforeStoreRegistrationEvent $event)
    {
        $registration = $event->getRegistration();

        if (empty($registration->getCategory()->getIconIdentifier())) {
            $iconName = 'apps-pagetree-page-' . strtolower($registration->getObject()->getName()) . '-category';

            $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
            $iconRegistry->registerIcon($iconName, AppIconProvider::class, [
                'registration' => $registration->getIdentifier()
            ]);

            $iconRegistry->registerIcon($iconName . '-hideinmenu', AppIconProvider::class, [
                'registration' => $registration->getIdentifier(),
                'hideInMenu' => true
            ]);

            $registration->getCategory()->setIconIdentifier($iconName);
        }
    }
}
