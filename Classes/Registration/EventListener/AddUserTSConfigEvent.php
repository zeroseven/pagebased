<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\EventListener;

use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedUserTsConfigEvent;
use Zeroseven\Pagebased\Registration\RegistrationService;

/**
 * Makes the registered category page types selectable in the "create new page" drag area.
 *
 * TYPO3 v14 removed ExtensionManagementUtility::addUserTSConfig(); user TSConfig is now
 * contributed while it is loaded, via BeforeLoadedUserTsConfigEvent. The registrations are
 * read from the RegistrationService (populated during Registration::store() in ext_localconf.php).
 */
class AddUserTSConfigEvent
{
    public function __invoke(BeforeLoadedUserTsConfigEvent $beforeLoadedUserTsConfigEvent): void
    {
        $documentTypes = [];

        foreach (RegistrationService::getRegistrations() as $registration) {
            if (($documentType = $registration->getCategory()->getDocumentType()) !== 0) {
                $documentTypes[] = $documentType;
            }
        }

        if ($documentTypes !== []) {
            $beforeLoadedUserTsConfigEvent->addTsConfig(sprintf(
                'options.pageTree.doktypesToShowInNewPageDragArea := addToList(%s)',
                implode(',', $documentTypes)
            ));
        }
    }
}
