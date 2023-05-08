<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\EventListener;

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\RootLineUtility;
use Zeroseven\Rampage\Utility\SettingsUtility;

class BeforeFormInitializedEventListener
{
    protected ?QueryBuilder $queryBuilder = null;

    protected function updatePageRecord(int $uid, array $update): void
    {
        $queryBuilder = $this->queryBuilder ?? $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(AbstractPage::TABLE_NAME);

        $statement = $queryBuilder->update(AbstractPage::TABLE_NAME)->where($queryBuilder->expr()->eq('uid', $uid));

        foreach ($update as $field => $value) {
            $statement->set($field, $value);
        }

        try {
            $statement->executeStatement();
        } catch (DBALException $e) {
        }
    }

    public function __invoke(BeforeFormEnginePageInitializedEvent $event): void
    {
        $parsedBody = $event->getRequest()->getParsedBody();
        $queryParams = $event->getRequest()->getQueryParams();

        if (
            ($editConfiguration = $parsedBody['edit'] ?? $queryParams['edit'] ?? null)
            && ($table = array_key_first($editConfiguration))
            && $table === AbstractPage::TABLE_NAME
            && $uid = (int)(array_key_first($editConfiguration[$table] ?? []))
        ) {
            $isCategory = RegistrationService::getRegistrationByCategoryPageUid($uid) !== null;
            $registration = RegistrationService::getObjectRegistrationInRootLine($uid);

            $update = [
                SettingsUtility::SITE_FIELD_NAME => 0,
                SettingsUtility::REGISTRATION_FIELD_NAME => ''
            ];

            if (($isCategory || $registration) && $rootPage = RootLineUtility::getRootPage($uid)) {
                $update[SettingsUtility::SITE_FIELD_NAME] = $rootPage;
            }

            if ($registration) {
                $update[SettingsUtility::REGISTRATION_FIELD_NAME] = $registration->getIdentifier();
            }

            $this->updatePageRecord($uid, $update);
        }
    }
}
