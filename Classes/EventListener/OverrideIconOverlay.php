<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\Event\ModifyRecordOverlayIconIdentifierEvent;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Registration\EventListener\IconRegistryEvent;
use Zeroseven\Pagebased\Utility\ObjectUtility;


class OverrideIconOverlay
{
    #[AsEventListener('pagebased/override-icon-overlay')]
    public function __invoke(ModifyRecordOverlayIconIdentifierEvent $event): void
    {
        $table = $event->getTable();
        $row = $event->getRow();
        $status = $event->getStatus();
        $iconName = $event->getOverlayIconIdentifier();

        if ($table === AbstractPage::TABLE_NAME && empty($iconName) && $uid = (int)($row['uid'] ?? 0)) {
            if ($registration = ObjectUtility::isObject($uid)) {
                if ($object = $registration->getObject()->getRepositoryClass()->findByUid($uid)) {
                    if ($object->isTop()) {
                        $event->setOverlayIconIdentifier('overlay-approved');
                        return;
                    }

                    if ($object->getParentObject()) {
                        $event->setOverlayIconIdentifier('overlay-advanced');
                        return;
                    }
                }

                $event->setOverlayIconIdentifier(IconRegistryEvent::getOverlayIconName($registration));
                return;
            }

            if (($registration = ObjectUtility::isCategory($uid)) && $category = $registration->getCategory()->getRepositoryClass()->findByUid($uid)) {
                if ($category->getRedirectCategory()) {
                    $event->setOverlayIconIdentifier('overlay-shortcut');
                }
            }
        }
    }
}
