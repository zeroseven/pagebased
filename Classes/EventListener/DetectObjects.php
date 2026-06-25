<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\EventListener;

use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Utility\DetectionUtility;

class DetectObjects
{
    public function __invoke(BeforeFormEnginePageInitializedEvent $beforeFormEnginePageInitializedEvent): void
    {
        $parsedBody = $beforeFormEnginePageInitializedEvent->getRequest()->getParsedBody();
        $queryParams = $beforeFormEnginePageInitializedEvent->getRequest()->getQueryParams();

        if (
            ($editConfiguration = $parsedBody['edit'] ?? $queryParams['edit'] ?? null)
            && ($table = array_key_first($editConfiguration))
            && $table === AbstractPage::TABLE_NAME
            && $uid = (int)(array_key_first($editConfiguration[$table] ?? []))
        ) {
            DetectionUtility::updateFields($uid);
        }
    }
}
