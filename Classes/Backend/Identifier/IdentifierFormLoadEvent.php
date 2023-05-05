<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Backend\Identifier;

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Utility\RootLineUtility;

class IdentifierFormLoadEvent
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

        if (($editConfiguration = $parsedBody['edit'] ?? $queryParams['edit'] ?? null) && ($table = array_key_first($editConfiguration)) && $uid = (int)(array_key_first($editConfiguration[$table] ?? []))) {
            $detector = GeneralUtility::makeInstance(IdentifierDetector::class, $uid, $table);
            $categoryRegistration = $detector->getCategoryRegistration();
            $objectRegistration = $detector->getObjectRegistration();

            $update = [
                IdentifierDetector::SITE_FIELD_NAME => 0,
                IdentifierDetector::OBJECT_FIELD_NAME => ''
            ];

            if ($categoryRegistration || $objectRegistration) {
                if ($rootPage = RootLineUtility::getRootPage($uid)) {
                    $update[IdentifierDetector::SITE_FIELD_NAME] = $rootPage;
                }
            }

            if ($objectRegistration) {
                $update[IdentifierDetector::OBJECT_FIELD_NAME] = $objectRegistration->getClassName();
            }

            $this->updatePageRecord($uid, $update);
        }
    }
}
