<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Zeroseven\Rampage\Domain\Model\PageTypeInterface;
use Zeroseven\Rampage\Registration\PageObjectRegistration;
use Zeroseven\Rampage\Registration\PluginRegistration;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

class AddTCAEvent
{
    protected function createPlugin(Registration $registration, PluginRegistration $pluginRegistration): void
    {
        $CType = $pluginRegistration->getCType($registration);

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

    protected function createPageType(PageObjectRegistration $pageObjectRegistration): void
    {
        if (is_subclass_of($pageObjectRegistration->getObjectClassName(), PageTypeInterface::class) && $documentType = $pageObjectRegistration->getObjectClassName()::getType()) {

            // Add to type list
            if (($tcaTypeField = $GLOBALS['TCA']['pages']['ctrl']['type'] ?? null)) {
                ExtensionManagementUtility::addTcaSelectItem(
                    'pages',
                    $tcaTypeField,
                    [
                        $pageObjectRegistration->getTitle(),
                        $documentType,
                        $pageObjectRegistration->getIconIdentifier()
                    ],
                    '1',
                    'after'
                );
            }

            // Add basic fields
            $GLOBALS['TCA']['pages']['types'][$documentType]['showitem'] = $GLOBALS['TCA']['pages']['types'][1]['showitem'];

            // Add icon
            $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$documentType] = $pageObjectRegistration->getIconIdentifier();
            $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$documentType . '-hideinmenu'] = $pageObjectRegistration->getIconIdentifier(true);
        }
    }

    protected function addPageType(Registration $registration): void
    {
        if (($pageObject = $registration->getObject()) && $pageObject->isEnabled()) {
            $this->createPageType($pageObject);
        }
    }

    protected function addPageCategory(Registration $registration): void
    {
        if (($pageCategory = $registration->getCategory()) && $pageCategory->isEnabled()) {
            $this->createPageType($pageCategory);
        }
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
            $this->createPlugin($registration, $registration->getFilterPlugin());
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
