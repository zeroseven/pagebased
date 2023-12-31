<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Repository;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception as PersistenceException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap\Relation;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;
use Zeroseven\Pagebased\Exception\RegistrationException;
use Zeroseven\Pagebased\Exception\TypeException;
use Zeroseven\Pagebased\Utility\CastUtility;

abstract class AbstractRepository extends Repository
{
    /** @throws RegistrationException */
    abstract protected function initializeDemand(): DemandInterface;

    public function getDefaultQuerySettings(): QuerySettingsInterface
    {
        return $this->defaultQuerySettings;
    }

    /** @throws PersistenceException */
    public function setOrdering(DemandInterface $demand = null): void
    {
        if (
            $demand
            && $demand->getOrderBy()
            && preg_match('/([a-zA-Z]+)(?:_(asc|desc))?/', $demand->getOrderBy(), $matches) // Examples: "date_desc", "title_asc", "title",
            && ($property = $matches[1] ?? null)
        ) {
            if ($columnMap = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($this->objectType)->getColumnMap($property)) {
                $columnName = $columnMap->getColumnName();
            } else {
                $columnName = $property;
            }

            $this->setDefaultOrderings([
                $columnName => ($direction = $matches[2] ?? null) && $direction === 'desc' ? QueryInterface::ORDER_DESCENDING : QueryInterface::ORDER_ASCENDING
            ]);
        }
    }

    /** @throws AspectNotFoundException | InvalidQueryException | PersistenceException */
    public function createDemandConstraints(DemandInterface $demand, QueryInterface $query): array
    {
        $constraints = [];
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);

        // Search for specific uids
        if ($uidList = $demand->getUidList()) {
            if (($languageUid = (int)GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id', 0)) > 0) {
                $dataMap = $dataMapper->getDataMap($this->objectType);

                $constraints[] = $query->logicalAnd(
                    $query->in($dataMap->getTranslationOriginColumnName(), $uidList),
                    $query->equals($dataMap->getLanguageIdColumnName(), $languageUid)
                );
            } else {
                $constraints[] = $query->in('uid', $uidList);
            }
        }

        foreach ($demand->getProperties() as $property) {
            if (($value = $property->getValue()) && ($propertyName = $property->getExtbasePropertyName()) && $columnMap = $dataMapper->getDataMap($this->objectType)->getColumnMap($propertyName)) {
                if ($property->isArray()) {
                    if (in_array($columnMap->getTypeOfRelation(), [Relation::HAS_MANY, Relation::HAS_AND_BELONGS_TO_MANY], true)) {
                        $constraints[] = $query->logicalOr(...array_map(static fn($v) => $query->contains($propertyName, $v), $value));
                    } elseif ($columnMap->getTypeOfRelation() === Relation::NONE) {
                        $constraints[] = $query->logicalOr(...array_map(static fn($v) => $query->like($propertyName, '%' . $v . '%'), $value));
                    } else {
                        $constraints[] = $query->contains($propertyName, $value);
                    }
                } elseif ($property->isString()) {
                    $constraints[] = $query->like($propertyName, '%' . $value . '%');
                } else {
                    $constraints[] = $query->equals($propertyName, $value);
                }
            }
        }

        return $constraints;
    }

    public function getDefaultOrderings(): array
    {
        return $this->defaultOrderings;
    }

    protected function orderByUid(mixed $orderReference, QueryResultInterface $objects): QueryResultInterface
    {
        // Create ordered list
        try {
            $sortedList = array_fill_keys(CastUtility::array($orderReference), null);
        } catch (TypeException $e) {
            return $objects;
        }

        // Assign objects
        foreach ($objects as $object) {
            if ($uid = $object->getUid()) {
                $sortedList[$uid] = $object;
            }
        }

        // Remove empty objects
        $sortedList = array_filter($sortedList, static function ($o) {
            return $o;
        });

        // Resort objects in result
        foreach ($objects as $key => $value) {
            $objects->offsetSet($key, array_shift($sortedList));
        }

        return $objects;
    }

    /** @throws AspectNotFoundException | InvalidQueryException | PersistenceException */
    public function findByDemand(DemandInterface $demand, ?QueryInterface $query = null): ?QueryResultInterface
    {
        // Override sorting
        $this->setOrdering($demand);

        // Create query
        $query = $query ?? $this->createQuery();

        // Apply constraints
        if (!empty($constraints = $this->createDemandConstraints($demand, $query))) {
            $query->matching(
                $query->logicalAnd(...$constraints)
            );
        }

        // Set limit
        $query->setLimit(($maxItems = $demand->getMaxItems()) === 0 ? 1000 : $maxItems);

        // Execute
        if ($demand->getOrderBy() === 'manual' && $uidList = $demand->getUidList()) {
            return $this->orderByUid($uidList, $query->execute());
        }

        return $query->execute();
    }

    /** @throws AspectNotFoundException | InvalidQueryException | PersistenceException | RegistrationException */
    public function findByUidList(mixed $uidList, DemandInterface $demand = null): ?QueryResultInterface
    {
        return $this->findByDemand(($demand ?? $this->initializeDemand())->setUidList($uidList));
    }

    /** @throws AspectNotFoundException | InvalidQueryException | PersistenceException | RegistrationException */
    public function findAll(DemandInterface $demand = null): ?QueryResultInterface
    {
        return $this->findByDemand($demand ?? $this->initializeDemand());
    }

    /** @throws AspectNotFoundException | TypeException | InvalidQueryException | PersistenceException | RegistrationException */
    public function findByUid(mixed $uid, bool $ignoreRestrictions = null): ?DomainObjectInterface
    {
        $uid = CastUtility::int($uid);
        $query = $this->createQuery();

        if ($ignoreRestrictions === true) {
            $query->getQuerySettings()->setIgnoreEnableFields(true)->setIncludeDeleted(true)->setRespectStoragePage(false);
        }

        if ($results = $this->findByDemand($this->initializeDemand()->setUidList([$uid]), $query)) {
            return $results->getFirst();
        }

        return null;
    }
}
