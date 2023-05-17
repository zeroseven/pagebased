<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Zeroseven\Rampage\Registration\Event\StoreRegistrationEvent;
use Zeroseven\Rampage\Registration\AbstractPluginRegistration;
use Zeroseven\Rampage\Registration\Registration;

class AddTSConfigEvent
{
    protected ?Registration $registration;

    protected function addContentWizard(?AbstractPluginRegistration $plugin = null): void
    {
        if ($plugin && $this->registration) {
            $cType = $plugin->getCType($this->registration);

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

    public function __invoke(StoreRegistrationEvent $event): void
    {
        $this->registration = $event->getRegistration();

        $this->addContentWizard($this->registration->getListPlugin());
        $this->addContentWizard($this->registration->getFilterPlugin());
    }
}
