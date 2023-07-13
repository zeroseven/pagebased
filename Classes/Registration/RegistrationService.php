<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration;

use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Domain\Model\CategoryInterface;
use Zeroseven\Pagebased\Exception\ValueException;

class RegistrationService
{
    /** @return Registration[] */
    public static function getRegistrations(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['registrations'] ?? [];
    }

    public static function addRegistration(Registration $registration): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['registrations'][$registration->getIdentifier()] = $registration;
    }

    public static function getRegistrationByClassName(string $className): ?Registration
    {
        foreach (self::getRegistrations() as $registration) {
            if ($registration->getObject()->getClassName() === $className) {
                return $registration;
            }
        }

        return null;
    }

    public static function getRegistrationByController(string $className): ?Registration
    {
        foreach (self::getRegistrations() as $registration) {
            if ($registration->getObject()->getControllerClassName() === $className) {
                return $registration;
            }
        }

        return null;
    }

    public static function getRegistrationByRepository(string $className): ?Registration
    {
        foreach (self::getRegistrations() as $registration) {
            if ($registration->getObject()->getRepositoryClassName() === $className) {
                return $registration;
            }
        }

        return null;
    }

    public static function getRegistrationByDemandClass(string $className): ?Registration
    {
        foreach (self::getRegistrations() as $registration) {
            if ($registration->getObject()->getDemandClassName() === $className) {
                return $registration;
            }
        }

        return null;
    }

    public static function getRegistrationByCategoryClassName(string $className): ?Registration
    {
        foreach (self::getRegistrations() as $registration) {
            if ($registration->getCategory()->getClassName() === $className) {
                return $registration;
            }
        }

        return null;
    }

    public static function getRegistrationByCategoryDocumentType(int $documentType): ?Registration
    {
        foreach (self::getRegistrations() as $registration) {
            if ($registration->getCategory()->getDocumentType() === $documentType) {
                return $registration;
            }
        }

        return null;
    }

    /** @throws ValueException */
    public static function getRegistrationByIdentifier(string $identifier): Registration
    {
        $registrations = self::getRegistrations();

        if (!isset($registrations[$identifier])) {
            $validIdentifier = array_map(static fn($registration) => '"' . $registration->getIdentifier() . '"', $registrations);

            throw new ValueException(sprintf('Registration "%s" not found. Use one of the following identifier %s', $identifier, implode(', ', $validIdentifier)), 1623157889);
        }

        return $registrations[$identifier];
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

            if (!isset($configuration['recordType']) && is_subclass_of($className, CategoryInterface::class) && $registration = self::getRegistrationByCategoryClassName($className)) {
                $classConfiguration[$className]['recordType'] = $registration->getCategory()->getDocumentType();
            }
        }

        return $classConfiguration;
    }
}
