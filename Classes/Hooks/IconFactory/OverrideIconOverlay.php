<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Hooks\IconFactory;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\DetectionUtility;

class OverrideIconOverlay
{
    public function postOverlayPriorityLookup(string $table, array $row, array $status, string $iconName = null): ?string
    {
        if ($table === AbstractPage::TABLE_NAME && empty($iconName)) {
            $uid = $row['uid'] ?? 0;
            $typeField = $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['type'];

            if (!isset($row[$typeField], $row[DetectionUtility::REGISTRATION_FIELD_NAME])) {
                $row = (array)BackendUtility::getRecord(AbstractPage::TABLE_NAME, $uid, implode(',', [$typeField, DetectionUtility::REGISTRATION_FIELD_NAME]));
            }

            if (
                ($identifier = $row[DetectionUtility::REGISTRATION_FIELD_NAME] ?? null)
                && ($registration = RegistrationService::getRegistrationByIdentifier($identifier))
                && ($object = $registration->getObject()->getRepositoryClass()->findByUid($uid))
                && $object->isTop()
            ) {
                return 'overlay-approved';
            }
        }

        return $iconName;
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][IconFactory::class]['overrideIconOverlay'][] = self::class;
    }
}
