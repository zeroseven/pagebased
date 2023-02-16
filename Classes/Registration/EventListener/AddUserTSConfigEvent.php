<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Zeroseven\Rampage\Domain\Model\PageTypeInterface;
use Zeroseven\Rampage\Registration\Event\StoreRegistrationEvent;
use Zeroseven\Rampage\Registration\Registration;

class AddUserTSConfigEvent
{
    protected ?Registration $registration;

    protected function addPageType(int $documentType): void
    {
        ExtensionManagementUtility::addUserTSConfig("options.pageTree.doktypesToShowInNewPageDragArea := addToList($documentType)");
    }

    protected function addPageTypes(): void
    {
        if (($pageObject = $this->registration->getObject()) && $pageObject->isEnabled() && is_subclass_of(($className = $pageObject->getObjectClassName()), PageTypeInterface::class)) {
            $this->addPageType($className::getType());
        }

        if (($categoryPage = $this->registration->getCategory()) && $categoryPage->isEnabled() && is_subclass_of(($className = $categoryPage->getObjectClassName()), PageTypeInterface::class)) {
            $this->addPageType($className::getType());
        }
    }

    public function __invoke(StoreRegistrationEvent $event): void
    {
        $this->registration = $event->getRegistration();
        $this->addPageTypes();
    }
}
