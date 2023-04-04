<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Repository;

use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception as PersistenceException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\RegistrationService;

abstract class AbstractObjectRepository extends AbstractPageRepository implements ObjectRepositoryInterface
{
    protected $defaultOrderings = [
        '_rampage_date' => QueryInterface::ORDER_DESCENDING,
        'uid' => QueryInterface::ORDER_ASCENDING
    ];

    /** @throws RegistrationException */
    protected function initializeDemand(): DemandInterface
    {
        return RegistrationService::getRegistrationByRepository(get_class($this))->getObject()->getDemandClass();
    }

    /** @throws PersistenceException */
    protected function setOrdering(DemandInterface $demand = null): void
    {
        parent::setOrdering($demand);

        if ($demand && $demand->getTopObjectFirst()) {
            $fieldName = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($this->objectType)->getColumnMap('top')->getColumnName();
            $this->setDefaultOrderings(array_merge([$fieldName => QueryInterface::ORDER_DESCENDING], $this->defaultOrderings));
        }
    }

    /** @throws AspectNotFoundException | InvalidQueryException | PersistenceException */
    protected function createDemandConstraints(DemandInterface $demand, QueryInterface $query): array
    {
        $constraints = parent::createDemandConstraints($demand, $query);

        if ($demand->getTopObjectOnly()) {
            $constraints[] = $query->equals('top', 1);
        }

        return $constraints;
    }
}
