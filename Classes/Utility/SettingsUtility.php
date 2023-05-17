<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use Zeroseven\Rampage\Registration\EventListener\AddTypoScriptEvent;
use Zeroseven\Rampage\Registration\Registration;

class SettingsUtility
{
    public const EXTENSION_NAME = 'rampage';

    protected static function getPropertyPath($subject, string $propertyPath = null)
    {
        if ($propertyPath === null) {
            return $subject;
        }

        return ObjectAccess::getPropertyPath((array)$subject, $propertyPath);
    }

    public static function getPluginConfiguration(Registration $registration, string $propertyPath = null): mixed
    {
        $extensionName = $registration->getExtensionName();

        if (empty($settings = $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/rampage']['settings'][$extensionName] ?? null)) {
            try {
                $settings = $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/rampage']['settings'][$extensionName] = GeneralUtility::makeInstance(ConfigurationManager::class)->getConfiguration(
                    ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
                    $extensionName,
                    AddTypoScriptEvent::getTypoScriptPluginKey($registration)
                ) ?: [];
            } catch (InvalidConfigurationTypeException $e) {
            }
        }

        return self::getPropertyPath($settings, $propertyPath);
    }
}
