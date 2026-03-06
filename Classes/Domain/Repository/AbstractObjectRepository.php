<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Repository;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception as PersistenceException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;
use Zeroseven\Pagebased\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Pagebased\Exception\RegistrationException;
use Zeroseven\Pagebased\Exception\TypeException;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;
use Zeroseven\Pagebased\Utility\CastUtility;
use Zeroseven\Pagebased\Utility\DetectionUtility;
use Zeroseven\Pagebased\Utility\RootLineUtility;

abstract class AbstractObjectRepository extends AbstractPageRepository implements ObjectRepositoryInterface
{
    protected Registration $registration;

    public function __construct()
    {
        parent::__construct();

        $this->registration = RegistrationService::getRegistrationByRepository(static::class);

        $this->defaultOrderings = [
            $this->registration->getObject()->getSortingField() =>
                $this->registration->getObject()->isSortingAscending()
                    ? QueryInterface::ORDER_ASCENDING
                    : QueryInterface::ORDER_DESCENDING,
        ];
    }

    public function initializeDemand(): DemandInterface
    {
        return RegistrationService::getRegistrationByRepository(static::class)?->getObject()->getDemandClass();
    }

    /** @throws PersistenceException */
    public function setOrdering(DemandInterface $demand = null): void
    {
        parent::setOrdering($demand);

        if ($demand && $demand->getTopObjectFirst() && $fieldName = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($this->objectType)->getColumnMap('top')->getColumnName()) {
            $this->setDefaultOrderings(array_merge([$fieldName => QueryInterface::ORDER_DESCENDING], $this->defaultOrderings));
        }
    }

    /** @throws AspectNotFoundException|InvalidQueryException|PersistenceException */
    public function createDemandConstraints(DemandInterface $demand, QueryInterface $query): array
    {
        $constraints = parent::createDemandConstraints($demand, $query);

        // Stay in the hood
        if ($query->getQuerySettings()->getRespectStoragePage() === false && $demand->getCategory() >= 0 && $startPageId = RootLineUtility::getRootPage()) {
            $constraints[] = $query->equals(DetectionUtility::SITE_FIELD_NAME, $startPageId);
        }

        // Search in category
        if (empty($demand->getUidList()) && ($categoryUid = $demand->getCategory()) > 0) {
            $treeTableField = (int)GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id', 0) > 0
                ? $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['transOrigPointerField']
                : 'uid';

            $constraints[] = $query->in($treeTableField, array_keys(RootLineUtility::collectPagesBelow($categoryUid)));
        }

        // Search by object identifier
        $constraints[] = $query->logicalAnd(
            $query->equals(DetectionUtility::REGISTRATION_FIELD_NAME, $this->registration->getIdentifier()),
            $query->logicalNot(
                $query->equals($GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['type'], $this->registration->getCategory()->getDocumentType())
            )
        );

        // Exclude child objects
        if ($demand->getIncludeChildObjects() === false && empty($demand->getUidList())) {
            $constraints[] = $query->logicalNot($query->equals(DetectionUtility::CHILD_OBJECT_FIELD_NAME, 1));
        }

        // Check for top objects
        if ($this->registration->getObject()->topEnabled() && $demand->getTopObjectOnly() && $fieldName = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($this->objectType)->getColumnMap('top')->getColumnName()) {
            $constraints[] = $query->equals($fieldName, 1);
        }

        return $constraints;
    }

    /** @throws TypeException|InvalidQueryException */
    public function findByUid(mixed $uid, bool $ignoreRestrictions = null): ?DomainObjectInterface
    {
        $uid = CastUtility::int($uid);
        if ($uid <= 0) {
            return null;
        }

        $query = $this->createQuery();

        if ($ignoreRestrictions === true) {
            $query->getQuerySettings()->setIgnoreEnableFields(true)->setIncludeDeleted(true)->setRespectStoragePage(false);
        }

        $query->matching($query->logicalAnd(
            $query->equals('uid', $uid),
            $query->equals(DetectionUtility::REGISTRATION_FIELD_NAME, $this->registration->getIdentifier())
        ));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * Fetches only the raw tag CSV strings from the pages table for this registration,
     * bypassing full Extbase object hydration. Results are cached in TYPO3's data
     * cache and automatically invalidated when pages are modified.
     *
     * @param ObjectDemandInterface $demand Used for optional category-tree filtering.
     * @return string[] Raw comma-separated tag strings, one entry per page row.
     */
    public function findTagStrings(ObjectDemandInterface $demand): array
    {
        $categoryUid = $demand->getCategory();
        $cacheKey = 'pagebased_tags_' . md5(
            $this->registration->getIdentifier() . '_' . $categoryUid
        );

        $cache = $this->getTagsCache();
        if ($cache !== null && ($cached = $cache->get($cacheKey)) !== false) {
            return $cached;
        }

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(AbstractPage::TABLE_NAME);

        $qb->select('uid', 'pagebased_tags')
            ->from(AbstractPage::TABLE_NAME)
            ->where(
                $qb->expr()->eq(
                    DetectionUtility::REGISTRATION_FIELD_NAME,
                    $qb->createNamedParameter($this->registration->getIdentifier())
                ),
                $qb->expr()->neq(
                    $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['type'],
                    $qb->createNamedParameter($this->registration->getCategory()->getDocumentType(), \Doctrine\DBAL\ParameterType::INTEGER)
                ),
                $qb->expr()->neq('pagebased_tags', $qb->createNamedParameter(''))
            );

        if ($categoryUid > 0) {
            $pageIds = array_keys(RootLineUtility::collectPagesBelow($categoryUid));
            if (!empty($pageIds)) {
                $qb->andWhere($qb->expr()->in('uid', $qb->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)));
            } else {
                return [];
            }
        }

        $rows = $qb->executeQuery()->fetchAllAssociative();
        $result = array_column($rows, 'pagebased_tags');

        $cacheTags = array_map(static fn(array $row) => 'pageId_' . $row['uid'], $rows);
        $cache?->set($cacheKey, $result, $cacheTags ?: ['pagebased_tags']);

        return $result;
    }

    private function getTagsCache(): ?FrontendInterface
    {
        try {
            return GeneralUtility::makeInstance(CacheManager::class)->getCache('pagebased_tags');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function findChildObjects(mixed $value): ?QueryResultInterface
    {
        $query = $this->createQuery();

        try {
            $query->getQuerySettings()->setRespectStoragePage(true)->setStoragePageIds([CastUtility::int($value)]);
            $demand = $this->initializeDemand()->setIncludeChildObjects(true);

            return $this->findByDemand($demand, $query);
        } catch (RegistrationException|TypeException|AspectNotFoundException|InvalidQueryException|PersistenceException $e) {
        }

        return null;
    }

    /** @throws AspectNotFoundException|TypeException|InvalidQueryException|PersistenceException|RegistrationException */
    public function findParentObject(mixed $value): ?DomainObjectInterface
    {
        return ($uid = $value instanceof AbstractPage ? $value->getUid() : CastUtility::int($value))
        && ($parentPage = RootLineUtility::getParentPage($uid))
            ? $this->findByUid($parentPage)
            : null;
    }
}
