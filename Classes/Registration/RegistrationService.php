<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class RegistrationService
{
    public static function createRegistration(string $extensionName, string $objectClassName, string $repositoryClassName,  string $controllerClassName): Registration
    {
        return GeneralUtility::makeInstance(Registration::class, $extensionName, $objectClassName, $repositoryClassName, $controllerClassName);
    }

    /** @return Registration[] */
    public static function getRegistrations(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven-rampage']['registrations'] ?? [];
    }

    public static function addRegistration(Registration $registration): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven-rampage']['registrations'][$registration->getExtensionName() . '-' . md5($registration->getObject()->getObjectClassName())] = $registration;
    }

    public static function getRegistrationByController($className): ?Registration
    {
        foreach (self::getRegistrations() as $registration) {
            if ($registration->getObject()->getControllerClassName() === $className) {
                return $registration;
            }
        }

        return null;
    }
}
