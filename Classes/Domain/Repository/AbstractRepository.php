<?php

namespace Zeroseven\Rampage\Domain\Repository;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Utility\CastUtility;

abstract class AbstractRepository extends Repository
{
    protected function initializeDemand(): ?DemandInterface
    {
        return null;
    }

    protected function setOrdering(DemandInterface $demand = null): void
    {
        try {
            if (
                $demand
                && $demand->getOrderBy()
                && preg_match('/([a-zA-Z]+)(?:_(asc|desc))?/', $demand->getOrderBy(), $matches) // Examples: "date_desc", "title_asc", "title",
                && ($property = $matches[1] ?? null)
                && ($dataMapper = $this->objectManager->get(DataMapper::class))
                && ($columnMap = $dataMapper->getDataMap($this->objectType)->getColumnMap($property))
                && ($columnName = $columnMap->getColumnName())
            ) {
                $this->setDefaultOrderings([
                    $columnName => ($direction = $matches[2] ?? null) && $direction === 'desc' ? QueryInterface::ORDER_DESCENDING : QueryInterface::ORDER_ASCENDING
                ]);
            }
        } catch (Exception $e) {
        }
    }

    protected function createDemandConstraints(DemandInterface $demand, QueryInterface $query): array
    {
        $constraints = [];
        $dataMapper = $this->objectManager->get(DataMapper::class);

        // Search for specific uids
        if ($uidList = $demand->getUidList()) {
            if (($langaugeUid = (int)GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id', 0)) > 0) {
                try {
                    $dataMap = $dataMapper->getDataMap($this->objectType);
                } catch (Exception $e) {
                    return [];
                }

                $constraints[] = $query->logicalAnd(
                    $query->in($dataMap->getTranslationOriginColumnName(), $uidList),
                    $query->equals($dataMap->getLanguageIdColumnName(), $langaugeUid)
                );
            } else {
                try {
                    $constraints[] = $query->in('uid', $uidList);
                } catch (InvalidQueryException $e) {
                    return [];
                }
            }
        }

        foreach ($demand->getProperties() as $property) {
            if (($value = $property->getValue()) && ($propertyName = $property->getName()) && $columnMap = $dataMapper->getDataMap($this->objectType)->getColumnMap($propertyName)) {
                if ($property->isArray()) {
                    if (in_array($columnMap->getTypeOfRelation(), [ColumnMap::RELATION_HAS_MANY, ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY], true)) {
                        $constraints[] = $query->logicalOr(array_map(static function ($v) use ($query, $propertyName) {
                            return $query->contains($propertyName, $v);
                        }, $value));
                    } elseif ($columnMap->getTypeOfRelation() === ColumnMap::RELATION_NONE) {
                        $constraints[] = $query->logicalOr(array_map(static function ($v) use ($query, $propertyName) {
                            return $query->like($propertyName, '%' . $v . '%');
                        }, $value));
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

    public function findByDemand(DemandInterface $demand): ?QueryResultInterface
    {
        // Override sorting
        $this->setOrdering($demand);

        // Create query
        $query = $this->createQuery();

        // Apply constraints
        if (!empty($constraints = $this->createDemandConstraints($demand, $query))) {
            $query->matching(
                $query->logicalAnd($constraints)
            );
        }

        // Execute
        if ($demand->getOrderBy() === 'manual' && $uidList = $demand->getUidList()) {
            return $this->orderByUid($uidList, $query->execute());
        }

        return $query->execute();
    }

    public function findAll(DemandInterface $demand = null): ?QueryResultInterface
    {
        return $this->findByDemand($demand ?? $this->initializeDemand());
    }

    public function findByUidList(mixed $uidList, DemandInterface $demand = null): ?QueryResultInterface
    {
        return $this->findByDemand(($demand ?? $this->initializeDemand())->setUidList($uidList));
    }
}