<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\EventListener;

use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedPageTsConfigEvent;
use Zeroseven\Pagebased\Registration\AbstractRegistrationPluginProperty;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;

/**
 * Adds "new content element" wizard entries for the registered list/filter plugins.
 *
 * TYPO3 v14 removed ExtensionManagementUtility::addPageTSConfig(); page TSConfig is now
 * contributed while it is loaded, via BeforeLoadedPageTsConfigEvent. The registrations are
 * read from the RegistrationService (populated during Registration::store() in ext_localconf.php).
 */
class AddTSConfigEvent
{
    public function __invoke(BeforeLoadedPageTsConfigEvent $beforeLoadedPageTsConfigEvent): void
    {
        foreach (RegistrationService::getRegistrations() as $registration) {
            $this->addContentWizard($beforeLoadedPageTsConfigEvent, $registration, $registration->getListPlugin());
            $this->addContentWizard($beforeLoadedPageTsConfigEvent, $registration, $registration->getFilterPlugin());
        }
    }

    protected function addContentWizard(BeforeLoadedPageTsConfigEvent $beforeLoadedPageTsConfigEvent, Registration $registration, ?AbstractRegistrationPluginProperty $registrationPluginProperty = null): void
    {
        if (!$registrationPluginProperty instanceof AbstractRegistrationPluginProperty) {
            return;
        }

        $cType = $registrationPluginProperty->getCType($registration);

        $beforeLoadedPageTsConfigEvent->addTsConfig(sprintf(
            'mod.wizards.newContentElement.wizardItems.special {
    elements.%s {
        iconIdentifier = %s
        title = %s
        description = %s
        tt_content_defValues {
            CType = %s
        }
    }
    show := addToList(%s)
}',
            $cType,
            $registrationPluginProperty->getIconIdentifier(),
            $registrationPluginProperty->getTitle(),
            $registrationPluginProperty->getDescription(),
            $cType,
            $cType
        ));
    }
}
