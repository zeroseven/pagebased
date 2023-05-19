<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Zeroseven\Rampage\Registration\Event\AfterStoreRegistrationEvent;
use Zeroseven\Rampage\Registration\Registration;

class AddUserTSConfigEvent
{
    protected ?Registration $registration;

    public function __invoke(AfterStoreRegistrationEvent $event): void
    {
        if ($type = $event->getRegistration()->getCategory()->getObjectType()) {
            ExtensionManagementUtility::addUserTSConfig("options.pageTree.doktypesToShowInNewPageDragArea := addToList($type)");
        }
    }
}
