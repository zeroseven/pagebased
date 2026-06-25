<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\EventListener;

use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use Zeroseven\Pagebased\Imaging\IconProvider\AppIconProvider;
use Zeroseven\Pagebased\Imaging\IconProvider\OverlayIconProvider;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;

/**
 * Registers the auto-generated registration icons.
 *
 * This runs on BootCompletedEvent instead of during Registration::store() (which usually happens
 * in ext_localconf.php), because TYPO3 v14 forbids instantiating the IconRegistry while
 * ext_localconf.php is being loaded. The icon identifiers are assigned earlier by
 * {@see IconRegistryEvent}; here we only register the actual icons. Must run before
 * {@see ValidateRegistrationEvent}, which asserts that the icons are registered.
 */
class RegisterIconsEvent
{
    public function __construct(private readonly IconRegistry $iconRegistry) {}

    public function __invoke(BootCompletedEvent $bootCompletedEvent): void
    {
        foreach (RegistrationService::getRegistrations() as $registration) {
            $this->registerCategoryIcons($registration);
            $this->registerOverlayIcon($registration);
        }
    }

    private function registerCategoryIcons(Registration $registration): void
    {
        // Only the auto-generated identifier is owned by us; a custom one is the consumer's responsibility.
        if ($registration->getCategory()->getIconIdentifier() !== IconRegistryEvent::getIconName($registration)) {
            return;
        }

        $this->registerIcon(IconRegistryEvent::getIconName($registration), AppIconProvider::class, [
            'registration' => $registration->getIdentifier(),
        ]);

        $this->registerIcon(IconRegistryEvent::getIconName($registration, true), AppIconProvider::class, [
            'registration' => $registration->getIdentifier(),
            'hideInMenu' => true,
        ]);
    }

    private function registerOverlayIcon(Registration $registration): void
    {
        if ($registration->getObject()->getOverlayIconIdentifier() !== IconRegistryEvent::getIconName($registration)) {
            return;
        }

        $this->registerIcon(IconRegistryEvent::getOverlayIconName($registration), OverlayIconProvider::class, [
            'registration' => $registration->getIdentifier(),
        ]);
    }

    /** @param array<string, mixed> $options */
    private function registerIcon(string $identifier, string $iconProviderClassName, array $options): void
    {
        if (!$this->iconRegistry->isRegistered($identifier)) {
            $this->iconRegistry->registerIcon($identifier, $iconProviderClassName, $options);
        }
    }
}
