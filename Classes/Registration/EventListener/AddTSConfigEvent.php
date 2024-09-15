<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\EventListener;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Zeroseven\Pagebased\Registration\Event\AfterStoreRegistrationEvent;
use Zeroseven\Pagebased\Registration\AbstractRegistrationPluginProperty;
use Zeroseven\Pagebased\Registration\Registration;

class AddTSConfigEvent
{
    protected ?Registration $registration;

    protected function addContentWizard(?AbstractRegistrationPluginProperty $plugin = null): void
    {
        if ($plugin && $this->registration) {
            $cType = $plugin->getCType($this->registration);

            // ExtensionManagementUtility::addPageTSConfig()
            // deprecated in TYPO3 v13 and will be removed with TYPO3 v14. - https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.0/Deprecation-101799-ExtensionManagementUtilityaddPageTSConfig.html
            // @extensionScannerIgnoreLine
            ExtensionManagementUtility::addPageTSConfig(sprintf("mod.wizards.newContentElement.wizardItems.special {\n
                elements.%s {\n
                    iconIdentifier = %s\n
                    title = %s\n
                    description = %s\n
                    tt_content_defValues {\n
                        CType = %s\n
                    }\n
                }\n

                show :=addToList(%s)
            }", $cType, $plugin->getIconIdentifier(), $plugin->getTitle(), $plugin->getDescription(), $cType, $cType));
        }
    }

    public function __invoke(AfterStoreRegistrationEvent $event): void
    {
        $this->registration = $event->getRegistration();

        $this->addContentWizard($this->registration->getListPlugin());
        $this->addContentWizard($this->registration->getFilterPlugin());
    }
}
