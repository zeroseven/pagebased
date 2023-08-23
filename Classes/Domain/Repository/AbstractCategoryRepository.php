<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;
use Zeroseven\Pagebased\Registration\RegistrationService;
use Zeroseven\Pagebased\Utility\DetectionUtility;
use Zeroseven\Pagebased\Utility\RootLineUtility;

abstract class AbstractCategoryRepository extends AbstractPageRepository implements CategoryRepositoryInterface
{
    public function __construct(ObjectManagerInterface $objectManager)
    {
        parent::__construct($objectManager);

        if ($registration = RegistrationService::getRegistrationByCategoryRepository(get_class($this))) {
            $this->defaultOrderings = [
                $registration->getCategory()->getSortingField() =>
                    $registration->getCategory()->isSortingAscending()
                        ? QueryInterface::ORDER_ASCENDING
                        : QueryInterface::ORDER_DESCENDING
            ];
        }
    }

    public function initializeDemand(): DemandInterface
    {
        return RegistrationService::getRegistrationByCategoryRepository(get_class($this))?->getCategory()->getDemandClass();
    }

    public function initializeObject(): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function createDemandConstraints(DemandInterface $demand, QueryInterface $query): array
    {
        $constraints = parent::createDemandConstraints($demand, $query);

        if ($query->getQuerySettings()->getRespectStoragePage() === false && $startPageId = RootLineUtility::getRootPage()) {
            $constraints[] = $query->equals(DetectionUtility::SITE_FIELD_NAME, $startPageId);
        }

        return $constraints;
    }
}
