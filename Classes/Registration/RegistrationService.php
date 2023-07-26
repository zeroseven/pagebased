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

    protected static function getClassName(mixed $value): ?string
    {
        if (is_object($value)) {
            return get_class($value);
        }

        if (is_string($value) && class_exists($value)) {
            return $value;
        }

        return null;
    }

    public static function getRegistrationByObjectClass(mixed $object): ?Registration
    {
        if ($className = self::getClassName($object)) {
            foreach (self::getRegistrations() as $registration) {
                if ($registration->getObject()->getClassName() === $className) {
                    return $registration;
                }
            }
        }

        return null;
    }

    public static function getRegistrationByController(mixed $controller): ?Registration
    {
        if ($className = self::getClassName($controller)) {
            foreach (self::getRegistrations() as $registration) {
                if ($registration->getObject()->getControllerClassName() === $className) {
                    return $registration;
                }
            }
        }

        return null;
    }

    public static function getRegistrationByRepository(mixed $repository): ?Registration
    {
        if ($className = self::getClassName($repository)) {
            foreach (self::getRegistrations() as $registration) {
                if ($registration->getObject()->getRepositoryClassName() === $className) {
                    return $registration;
                }
            }
        }

        return null;
    }

    public static function getRegistrationByDemand(mixed $demand): ?Registration
    {
        if ($className = self::getClassName($demand)) {
            foreach (self::getRegistrations() as $registration) {
                if ($registration->getObject()->getDemandClassName() === $className) {
                    return $registration;
                }
            }
        }

        return null;
    }

    public static function getRegistrationByCategoryClass(mixed $category): ?Registration
    {
        if ($className = self::getClassName($category)) {
            foreach (self::getRegistrations() as $registration) {
                if ($registration->getCategory()->getClassName() === $className) {
                    return $registration;
                }
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

            if (!isset($configuration['recordType']) && is_subclass_of($className, CategoryInterface::class) && $registration = self::getRegistrationByCategoryClass($className)) {
                $classConfiguration[$className]['recordType'] = $registration->getCategory()->getDocumentType();
            }
        }

        return $classConfiguration;
    }
}
