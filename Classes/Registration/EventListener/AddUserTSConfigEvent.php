<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\EventListener;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Zeroseven\Pagebased\Registration\Event\AfterStoreRegistrationEvent;
use Zeroseven\Pagebased\Registration\Registration;

class AddUserTSConfigEvent
{
    protected ?Registration $registration;

    public function __invoke(AfterStoreRegistrationEvent $event): void
    {
        if ($type = $event->getRegistration()->getCategory()->getDocumentType()) {
            ExtensionManagementUtility::addUserTSConfig("options.pageTree.doktypesToShowInNewPageDragArea := addToList($type)");
        }
    }
}
