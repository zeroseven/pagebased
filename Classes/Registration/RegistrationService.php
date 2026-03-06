<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration;

use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Domain\Model\CategoryInterface;
use Zeroseven\Pagebased\Exception\ValueException;

class RegistrationService
{
    /** @var array<string, Registration> Lookup index keyed by object class name */
    private static array $indexByObjectClass = [];

    /** @var array<string, Registration> Lookup index keyed by controller class name */
    private static array $indexByController = [];

    /** @var array<string, Registration> Lookup index keyed by repository class name */
    private static array $indexByRepository = [];

    /** @var array<string, Registration> Lookup index keyed by demand class name */
    private static array $indexByDemand = [];

    /** @var array<string, Registration> Lookup index keyed by category class name */
    private static array $indexByCategoryClass = [];

    /** @var array<string, Registration> Lookup index keyed by category repository class name */
    private static array $indexByCategoryRepository = [];

    /** @var array<int, Registration> Lookup index keyed by category document type */
    private static array $indexByDocumentType = [];

    private static function addToIndex(Registration $registration): void
    {
        $obj = $registration->getObject();
        $cat = $registration->getCategory();

        if ($c = $obj->getClassName()) {
            self::$indexByObjectClass[$c] = $registration;
        }
        if ($c = $obj->getControllerClassName()) {
            self::$indexByController[$c] = $registration;
        }
        if ($c = $obj->getRepositoryClassName()) {
            self::$indexByRepository[$c] = $registration;
        }
        if ($c = $obj->getDemandClassName()) {
            self::$indexByDemand[$c] = $registration;
        }
        if ($c = $cat->getClassName()) {
            self::$indexByCategoryClass[$c] = $registration;
        }
        if ($c = $cat->getRepositoryClassName()) {
            self::$indexByCategoryRepository[$c] = $registration;
        }
        if ($dt = $cat->getDocumentType()) {
            self::$indexByDocumentType[$dt] = $registration;
        }
    }

    /** @return Registration[] */
    public static function getRegistrations(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['registrations'] ?? [];
    }

    public static function addRegistration(Registration $registration): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['registrations'][$registration->getIdentifier()] = $registration;
        self::addToIndex($registration);
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
            return self::$indexByObjectClass[$className] ?? null;
        }

        return null;
    }

    public static function getRegistrationByController(mixed $controller): ?Registration
    {
        if ($className = self::getClassName($controller)) {
            return self::$indexByController[$className] ?? null;
        }

        return null;
    }

    public static function getRegistrationByRepository(mixed $repository): ?Registration
    {
        if ($className = self::getClassName($repository)) {
            return self::$indexByRepository[$className] ?? null;
        }

        return null;
    }

    public static function getRegistrationByDemand(mixed $demand): ?Registration
    {
        if ($className = self::getClassName($demand)) {
            return self::$indexByDemand[$className] ?? null;
        }

        return null;
    }

    public static function getRegistrationByCategoryClass(mixed $category): ?Registration
    {
        if ($className = self::getClassName($category)) {
            return self::$indexByCategoryClass[$className] ?? null;
        }

        return null;
    }

    public static function getRegistrationByCategoryRepository(mixed $repository): ?Registration
    {
        if ($className = self::getClassName($repository)) {
            return self::$indexByCategoryRepository[$className] ?? null;
        }

        return null;
    }

    public static function getRegistrationByCategoryDocumentType(int $documentType): ?Registration
    {
        return self::$indexByDocumentType[$documentType] ?? null;
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
