<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use Zeroseven\Rampage\Registration\EventListener\AddTypoScriptEvent;
use Zeroseven\Rampage\Registration\Registration;

class SettingsUtility
{
    public const EXTENSION_NAME = 'rampage';

    public static function getExtensionConfiguration(Registration $registration, string $propertyPath = null): mixed
    {
        $extensionName = $registration->getExtensionName();

        if (empty($configuration = $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/rampage']['configuration'][$extensionName] ?? null)) {
            try {
                $configuration = $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/rampage']['configuration'][$extensionName] = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($extensionName);
            } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException $e) {
            }
        }

        return ArrayPathUtility::getPath($configuration, $propertyPath);
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

        return ArrayPathUtility::getPath($settings, $propertyPath);
    }
}
