<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\EventListener;

use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Utility\DetectionUtility;

class DetectObjects
{
    public function __invoke(BeforeFormEnginePageInitializedEvent $event): void
    {
        $parsedBody = $event->getRequest()->getParsedBody();
        $queryParams = $event->getRequest()->getQueryParams();

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
