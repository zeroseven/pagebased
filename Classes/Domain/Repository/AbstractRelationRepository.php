<?php

namespace Zeroseven\Rampage\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

abstract class AbstractRelationRepository extends Repository
{
    public function initializeObject(): void
    {
        $storagePageIds = array_merge(...array_values(array_map(fn(Registration $registration) => $this->getRelationPageIds($registration), RegistrationService::getRegistrations())));

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(true)->setStoragePageIds($storagePageIds);
        $this->setDefaultQuerySettings($querySettings);
    }

    abstract protected function getRelationPageIds(Registration $registration): array;

    public function findByRegistration(Registration $registration): ?QueryResultInterface
    {
        if (!empty($pageIds = $this->getRelationPageIds($registration))) {
            $query = $this->createQuery();
            $query->getQuerySettings()->setStoragePageIds($pageIds)->setRespectStoragePage(true);

            return $query->execute();
        }

        return null;
    }
}
