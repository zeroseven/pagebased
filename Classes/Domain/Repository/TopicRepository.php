<?php

namespace Zeroseven\Rampage\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\Registration;

class TopicRepository extends Repository
{
    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING,
        'uid' => QueryInterface::ORDER_ASCENDING
    ];

    public function initializeObject(): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function findByRegistration(Registration $registration): ?QueryResultInterface
    {
        try {
            if ($registration->getObject()->topicsEnabled()) {
                $query = $this->createQuery();
                $query->getQuerySettings()->setStoragePageIds($registration->getObject()->getTopicPageIds())->setRespectStoragePage(true);

                return $query->execute();
            }
        } catch (RegistrationException $e) {
        }

        return null;
    }
}
