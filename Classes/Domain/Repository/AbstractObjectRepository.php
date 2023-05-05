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
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\IdentifierUtility;
use Zeroseven\Rampage\Utility\RootLineUtility;

abstract class AbstractObjectRepository extends AbstractPageRepository implements ObjectRepositoryInterface
{
    protected Registration $registration;

    protected $defaultOrderings = [
        '_rampage_date' => QueryInterface::ORDER_DESCENDING,
        'uid' => QueryInterface::ORDER_ASCENDING
    ];

    /** @throws RegistrationException */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        parent::__construct($objectManager);

        $this->registration = RegistrationService::getRegistrationByRepository(get_class($this));
    }

    /** @throws RegistrationException */
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
        if ($objectName = $this->registration->getObject()->getClassName()) {
            $constraints[] = $query->equals(IdentifierUtility::OBJECT_FIELD_NAME, $objectName);
        }

        if ($demand->getTopObjectOnly()) {
            $constraints[] = $query->equals('top', 1);
        }

        return $constraints;
    }
}
