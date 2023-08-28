<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Repository;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception as PersistenceException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;
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

        $this->registration = RegistrationService::getRegistrationByRepository(get_class($this));

        $this->defaultOrderings = [
            $this->registration->getObject()->getSortingField() =>
                $this->registration->getObject()->isSortingAscending()
                    ? QueryInterface::ORDER_ASCENDING
                    : QueryInterface::ORDER_DESCENDING
        ];
    }

    public function initializeDemand(): DemandInterface
    {
        return RegistrationService::getRegistrationByRepository(get_class($this))?->getObject()->getDemandClass();
    }

    /** @throws PersistenceException */
    public function setOrdering(DemandInterface $demand = null): void
    {
        parent::setOrdering($demand);

        if ($demand && $demand->getTopObjectFirst() && $fieldName = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($this->objectType)->getColumnMap('top')->getColumnName()) {
            $this->setDefaultOrderings(array_merge([$fieldName => QueryInterface::ORDER_DESCENDING], $this->defaultOrderings));
        }
    }

    /** @throws AspectNotFoundException | InvalidQueryException | PersistenceException */
    public function createDemandConstraints(DemandInterface $demand, QueryInterface $query): array
    {
        $constraints = parent::createDemandConstraints($demand, $query);

        // Stay in the hood
        if ($query->getQuerySettings()->getRespectStoragePage() === false && $startPageId = RootLineUtility::getRootPage()) {
            $constraints[] = $query->equals(DetectionUtility::SITE_FIELD_NAME, $startPageId);
        }

        // Search in category
        if (empty($demand->getUidList()) && $categoryUid = $demand->getCategory()) {
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

    /** @throws AspectNotFoundException | TypeException | InvalidQueryException | PersistenceException | RegistrationException */
    public function findParentObject(mixed $value): ?DomainObjectInterface
    {
        return ($uid = $value instanceof AbstractPage ? $value->getUid() : CastUtility::int($value))
        && ($parentPage = RootLineUtility::getParentPage($uid))
            ? $this->findByUid($parentPage)
            : null;
    }
}
