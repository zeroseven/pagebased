<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Imaging\IconProvider\AppIconProvider;
use Zeroseven\Rampage\Imaging\IconProvider\OverlayIconProvider;
use Zeroseven\Rampage\Registration\Event\BeforeStoreRegistrationEvent;
use Zeroseven\Rampage\Registration\Registration;

class IconRegistryEvent
{
    public static function getIconName(Registration $registration, bool $hideInMenu = null): string
    {
        return 'apps-pagetree-page-' . strtolower($registration->getObject()->getName()) . '-category' . ($hideInMenu ? '-hideinmenu' : '');
    }

    public static function getOverlayIconName(Registration $registration): string
    {
        return 'overlay-page-' . strtolower($registration->getObject()->getName());
    }

    public function __invoke(BeforeStoreRegistrationEvent $event)
    {
        $registration = $event->getRegistration();
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

        if (empty($registration->getCategory()->getIconIdentifier())) {
            $iconRegistry->registerIcon(self::getIconName($registration), AppIconProvider::class, [
                'registration' => $registration->getIdentifier()
            ]);

            $iconRegistry->registerIcon(self::getIconName($registration, true), AppIconProvider::class, [
                'registration' => $registration->getIdentifier(),
                'hideInMenu' => true
            ]);

            $registration->getCategory()->setIconIdentifier(self::getIconName($registration));
        }

        if (empty($registration->getObject()->getOverlayIconIdentifier())) {
            $iconRegistry->registerIcon(self::getOverlayIconName($registration), OverlayIconProvider::class, [
                'registration' => $registration->getIdentifier()
            ]);

            $registration->getObject()->setOverlayIconIdentifier(self::getIconName($registration));
        }
    }
}
