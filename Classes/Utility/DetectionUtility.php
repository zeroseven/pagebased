<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Domain\Model\AbstractPage;

class DetectionUtility
{
    public const REGISTRATION_FIELD_NAME = '_rampage_registration';
    public const SITE_FIELD_NAME = '_rampage_site';
    public const CHILD_OBJECT_FIELD_NAME = '_rampage_child_object';

    protected static function updatePageRecord(int $uid, array $update): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(AbstractPage::TABLE_NAME);

        $statement = $queryBuilder->update(AbstractPage::TABLE_NAME)->where($queryBuilder->expr()->eq('uid', $uid));

        foreach ($update as $field => $value) {
            $statement->set($field, $value);
        }

        try {
            $statement->executeStatement();
        } catch (DBALException $e) {
        }
    }

    public static function getUpdateFields(int $uid): array
    {
        $registration = ObjectUtility::isSystemPage($uid) ? null : ObjectUtility::findCategoryInRootLine($uid);

        return [
            self::SITE_FIELD_NAME => $registration ? RootLineUtility::getRootPage($uid) : 0,
            self::REGISTRATION_FIELD_NAME => $registration ? $registration->getIdentifier() : '',
            self::CHILD_OBJECT_FIELD_NAME => $registration && ObjectUtility::isChildObject($uid) ? 1 : 0
        ];
    }

    public static function updateFields(int $uid): void
    {
        self::updatePageRecord($uid, self::getUpdateFields($uid));
    }
}
