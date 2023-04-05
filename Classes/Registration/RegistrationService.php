<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Domain\Model\PageTypeInterface;
use Zeroseven\Rampage\Exception\RegistrationException;

class RegistrationService
{
    public static function createRegistration(string $extensionName): Registration
    {
        return GeneralUtility::makeInstance(Registration::class, $extensionName);
    }

    /** @return Registration[] */
    public static function getRegistrations(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven-rampage']['registrations'] ?? [];
    }

    /** @throws RegistrationException */
    public static function addRegistration(Registration $registration): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven-rampage']['registrations'][$registration->getExtensionName() . '-' . md5($registration->getObject()->getClassName())] = $registration;
    }

    /** @throws RegistrationException */
    public static function getRegistrationByClassName($className): ?Registration
    {
        foreach (self::getRegistrations() as $registration) {
            if ($registration->getObject()->getClassName() === $className) {
                return $registration;
            }
        }

        return null;
    }

    /** @throws RegistrationException */
    public static function getRegistrationByController($className): ?Registration
    {
        foreach (self::getRegistrations() as $registration) {
            if ($registration->getObject()->getControllerClassName() === $className) {
                return $registration;
            }
        }

        return null;
    }

    /** @throws RegistrationException */
    public static function getRegistrationByRepository($className): ?Registration
    {
        foreach (self::getRegistrations() as $registration) {
            if ($registration->getObject()->getRepositoryClassName() === $className) {
                return $registration;
            }
        }

        return null;
    }

    /** @throws RegistrationException */
    public static function getRegistrationByDemandClass($className): ?Registration
    {
        foreach (self::getRegistrations() as $registration) {
            if ($registration->getObject()->getRepositoryClassName() === $className) {
                return $registration;
            }
        }

        return null;
    }

    /** @throws RegistrationException */
    public static function getRegistrationByCategoryDocumentType(int $documentType): ?Registration
    {
        foreach (self::getRegistrations() as $registration) {
            if ($registration->getCategory()->getObjectType() === $documentType) {
                return $registration;
            }
        }

        return null;
    }

    public static function extbasePersistenceConfiguration(array $classConfiguration): array
    {
        foreach ($classConfiguration as $className => $configuration) {
            if (!is_array($configuration)) {
                $classConfiguration[$className] = [];
            }

            if (!isset($configuration['tableName']) && is_subclass_of($className, AbstractPage::class)) {
                $classConfiguration[$className]['tableName'] = AbstractPage::TABLE_NAME;
            }

            if (!isset($configuration['recordType']) && is_subclass_of($className, PageTypeInterface::class)) {
                $classConfiguration[$className]['recordType'] = $className::getType();
            }
        }

        return $classConfiguration;
    }
}
