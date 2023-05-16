<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Repository;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception as PersistenceException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\CastUtility;
use Zeroseven\Rampage\Utility\DetectionUtility;
use Zeroseven\Rampage\Utility\RootLineUtility;

abstract class AbstractObjectRepository extends AbstractPageRepository implements ObjectRepositoryInterface
{
    protected Registration $registration;

    protected string $childObjectConstraintKey;

    protected $defaultOrderings = [
        '_rampage_date' => QueryInterface::ORDER_DESCENDING,
        'uid' => QueryInterface::ORDER_ASCENDING
    ];

    public function __construct(ObjectManagerInterface $objectManager)
    {
        parent::__construct($objectManager);

        $this->childObjectConstraintKey = uniqid('', false);
        $this->registration = RegistrationService::getRegistrationByRepository(get_class($this));
    }

    public function initializeDemand(): DemandInterface
    {
        return RegistrationService::getRegistrationByRepository(get_class($this))->getObject()->getDemandClass();
    }

    /** @throws PersistenceException */
    public function setOrdering(DemandInterface $demand = null): void
    {
        parent::setOrdering($demand);

        if ($demand && $demand->getTopObjectFirst()) {
            $fieldName = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($this->objectType)->getColumnMap('top')->getColumnName();
            $this->setDefaultOrderings(array_merge([$fieldName => QueryInterface::ORDER_DESCENDING], $this->defaultOrderings));
        }
    }

    /** @throws AspectNotFoundException | InvalidQueryException | PersistenceException */
    public function createDemandConstraints(DemandInterface $demand, QueryInterface $query): array
    {
        $constraints = parent::createDemandConstraints($demand, $query);

        // Search in category
        if (empty($demand->getUidList()) && $categoryUid = $demand->getCategory()) {
            $treeTableField = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id') ? 'pid' : 'uid';
            $constraints[] = $query->in($treeTableField, array_keys(RootLineUtility::collectPagesBelow($categoryUid)));
        }

        // Search by object identifier
        $constraints[] = $query->logicalAnd(
            $query->equals(DetectionUtility::REGISTRATION_FIELD_NAME, $this->registration->getIdentifier()),
            $query->logicalNot(
                $query->equals($GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['type'], $this->registration->getCategory()->getObjectType()),
            )
        );

        // Exclude sub objects
        $constraints[$this->childObjectConstraintKey] = $query->logicalNot($query->equals(DetectionUtility::SUB_OBJECT_FIELD_NAME, 1));

        if ($demand->getTopObjectOnly()) {
            $constraints[] = $query->equals('top', 1);
        }

        return $constraints;
    }

    public function findChildObjects(mixed $value): ?QueryResultInterface
    {
        $query = $this->createQuery();

        try {
            $uid = CastUtility::int($value);
            $constraints = $this->createDemandConstraints($this->initializeDemand(), $query);
        } catch (AspectNotFoundException | InvalidQueryException | PersistenceException | RegistrationException | TypeException $e) {
            return null;
        }

        if (isset($constraints[$this->childObjectConstraintKey])) {
            unset($constraints[$this->childObjectConstraintKey]);
        }

        $query->getQuerySettings()->setRespectStoragePage(true)->setStoragePageIds([$uid]);
        $query->matching(...$constraints);

        return $query->execute();
    }
}
