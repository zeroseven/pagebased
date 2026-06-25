<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\EventListener;

use Zeroseven\Pagebased\Registration\Event\BeforeStoreRegistrationEvent;
use Zeroseven\Pagebased\Registration\Registration;

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

    public function __invoke(BeforeStoreRegistrationEvent $beforeStoreRegistrationEvent): void
    {
        $registration = $beforeStoreRegistrationEvent->getRegistration();

        // Only assign the auto-generated icon identifiers here. They must be available early
        // (e.g. for AddTCAEvent on AfterTcaCompilationEvent), but the icons themselves are
        // registered later by RegisterIconsEvent on BootCompletedEvent: TYPO3 v14 forbids
        // instantiating the IconRegistry while ext_localconf.php is still being loaded
        // (the typical place where Registration::store() is called).
        if (in_array($registration->getCategory()->getIconIdentifier(), ['', '0'], true)) {
            $registration->getCategory()->setIconIdentifier(self::getIconName($registration));
        }

        if (in_array($registration->getObject()->getOverlayIconIdentifier(), ['', '0'], true)) {
            $registration->getObject()->setOverlayIconIdentifier(self::getIconName($registration));
        }
    }
}
