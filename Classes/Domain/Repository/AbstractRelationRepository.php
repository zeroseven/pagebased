<?php

namespace Zeroseven\Pagebased\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;

abstract class AbstractRelationRepository extends Repository
{
    public function initializeObject(): void
    {
        $storagePageIds = array_merge(...array_values(array_map($this->getRelationPageIds(...), RegistrationService::getRegistrations())));

        $typo3QuerySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $typo3QuerySettings->setRespectStoragePage(true)->setStoragePageIds($storagePageIds);
        $this->setDefaultQuerySettings($typo3QuerySettings);
    }

    abstract protected function getRelationPageIds(Registration $registration): array;

    public function findByRegistration(Registration $registration): ?QueryResultInterface
    {
        if (($pageIds = $this->getRelationPageIds($registration)) !== []) {
            $query = $this->createQuery();
            $query->getQuerySettings()->setStoragePageIds($pageIds)->setRespectStoragePage(true);

            return $query->execute();
        }

        return null;
    }
}
