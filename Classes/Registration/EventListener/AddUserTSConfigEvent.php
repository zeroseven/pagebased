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
            // ExtensionManagementUtility::addUserTSConfig()
            // deprecated in TYPO3 v13 and will be removed with TYPO3 v14. - https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.0/Deprecation-101807-ExtensionManagementUtilityaddUserTSConfig.html
            // @extensionScannerIgnoreLine
            ExtensionManagementUtility::addUserTSConfig("options.pageTree.doktypesToShowInNewPageDragArea := addToList($type)");
        }
    }
}
