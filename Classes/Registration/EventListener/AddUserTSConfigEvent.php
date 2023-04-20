<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\Event\StoreRegistrationEvent;
use Zeroseven\Rampage\Registration\Registration;

class AddUserTSConfigEvent
{
    protected ?Registration $registration;

    /** @throws RegistrationException */
    protected function addPageTypes(): void
    {
        if (($categoryPage = $this->registration->getCategory()) && $type = $categoryPage->getObjectType()) {
            ExtensionManagementUtility::addUserTSConfig("options.pageTree.doktypesToShowInNewPageDragArea := addToList($type)");
        }
    }

    /** @throws RegistrationException */
    public function __invoke(StoreRegistrationEvent $event): void
    {
        $this->registration = $event->getRegistration();
        $this->addPageTypes();
    }
}
