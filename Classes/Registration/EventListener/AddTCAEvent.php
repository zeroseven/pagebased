<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use Zeroseven\Rampage\Registration\PluginRegistration;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

class AddTCAEvent
{
    protected function createPlugin(Registration $registration, PluginRegistration $pluginRegistration): void
    {
        $CType = str_replace('_', '', $registration->getExtensionName()) . '_' . $pluginRegistration->getType();

        // Add some default fields to the content elements by copy configuration of "header"
        $GLOBALS['TCA']['tt_content']['types'][$CType]['showitem'] = $GLOBALS['TCA']['tt_content']['types']['header']['showitem'];

        // Register plugins
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $registration->getExtensionName(),
            ucfirst($pluginRegistration->getType()),
            $pluginRegistration->getTitle(),
            $pluginRegistration->getIconIdentifier()
        );

        // Register icon
        $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$CType] = $pluginRegistration->getIconIdentifier();
    }

    protected function addPageType(Registration $registration): void
    {

    }

    protected function addPageCategory(Registration $registration): void
    {

    }

    protected function addListPlugin(Registration $registration): void
    {
        if ($registration->getListPlugin()->isEnabled()) {
            $this->createPlugin($registration, $registration->getListPlugin());
        }
    }

    protected function addFilterPlugin(Registration $registration): void
    {
        if ($registration->getFilterPlugin()->isEnabled()) {

        }
    }

    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        foreach (RegistrationService::getRegistrations() as $registration) {
            $this->addPageType($registration);
            $this->addPageCategory($registration);
            $this->addListPlugin($registration);
            $this->addFilterPlugin($registration);
        }

        $event->setTca($GLOBALS['TCA']);
    }
}
