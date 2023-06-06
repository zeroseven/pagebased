<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Domain\Model\Demand\GenericDemand;
use Zeroseven\Rampage\Domain\Model\Entity\PageObject;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\DetectionUtility;
use Zeroseven\Rampage\Utility\RootLineUtility;

abstract class AbstractCategoryRepository extends AbstractPageRepository implements CategoryRepositoryInterface
{
    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING,
        'uid' => QueryInterface::ORDER_ASCENDING
    ];

    public function initializeDemand(): DemandInterface
    {
        $className = get_class($this);

        foreach (RegistrationService::getRegistrations() as $registration) {
            if ($registration->getCategory()->getRepositoryClassName() === $className) {
                return $registration->getCategory()->getDemandClass();
            }
        }

        return GenericDemand::build(PageObject::class);
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
