<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Hooks\IconFactory;

use TYPO3\CMS\Core\Imaging\IconFactory;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Registration\EventListener\IconRegistryEvent;
use Zeroseven\Pagebased\Utility\ObjectUtility;

class OverrideIconOverlay
{
    public function postOverlayPriorityLookup(string $table, array $row, array $status, string $iconName = null): ?string
    {
        if ($table === AbstractPage::TABLE_NAME && empty($iconName) && $uid = (int)($row['uid'] ?? 0)) {
            if ($registration = ObjectUtility::isObject($uid)) {
                if ($object = $registration->getObject()->getRepositoryClass()->findByUid($uid)) {
                    if ($object->isTop()) {
                        return 'overlay-approved';
                    }

                    if ($object->getParentObject()) {
                        return 'overlay-advanced';
                    }
                }

                return IconRegistryEvent::getOverlayIconName($registration);
            }

            if (($registration = ObjectUtility::isCategory($uid)) && $category = $registration->getCategory()->getRepositoryClass()->findByUid($uid)) {
                if ($category->getRedirectCategory()) {
                    return 'overlay-shortcut';
                }
            }
        }

        return $iconName;
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][IconFactory::class]['overrideIconOverlay'][] = self::class;
    }
}
