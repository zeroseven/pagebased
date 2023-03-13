<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Zeroseven\Rampage\Domain\Model\PageTypeInterface;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\Event\StoreRegistrationEvent;
use Zeroseven\Rampage\Registration\Registration;

class AddUserTSConfigEvent
{
    protected ?Registration $registration;

    protected function addPageType(int $documentType): void
    {
        ExtensionManagementUtility::addUserTSConfig("options.pageTree.doktypesToShowInNewPageDragArea := addToList($documentType)");
    }

    /** @throws RegistrationException */
    protected function addPageTypes(): void
    {
        if (($pageObject = $this->registration->getObject()) && $type = $pageObject->getObjectType()) {
            $this->addPageType($type);
        }

        if (($categoryPage = $this->registration->getCategory()) && $type = $categoryPage->getObjectType()) {
            $this->addPageType($type);
        }
    }

    /** @throws RegistrationException */
    public function __invoke(StoreRegistrationEvent $event): void
    {
        $this->registration = $event->getRegistration();
        $this->addPageTypes();
    }
}
