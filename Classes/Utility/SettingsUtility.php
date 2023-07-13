<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use Zeroseven\Pagebased\Registration\EventListener\AddTypoScriptEvent;
use Zeroseven\Pagebased\Registration\Registration;

class SettingsUtility
{
    public const EXTENSION_NAME = 'pagebased';

    public static function getExtensionConfiguration(Registration $registration, string $propertyPath = null): mixed
    {
        $extensionName = $registration->getExtensionName();

        if (empty($configuration = $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['configuration'][$extensionName] ?? null)) {
            try {
                $configuration = $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['configuration'][$extensionName] = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($extensionName);
            } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException $e) {
            }
        }

        return ArrayPathUtility::getPath($configuration, $propertyPath);
    }

    public static function getPluginConfiguration(Registration $registration, string $propertyPath = null): mixed
    {
        $extensionName = $registration->getExtensionName();

        $pluginConfiguration = $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['plugin-settings'][$extensionName] ?? call_user_func(static function () use ($registration, $extensionName) {
                $pluginKey = AddTypoScriptEvent::getTypoScriptPluginKey($registration);
                try {
                    $pluginConfiguration = GeneralUtility::makeInstance(ConfigurationManager::class)
                            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)['plugin.'][$pluginKey . '.'] ?? [];
                } catch (InvalidConfigurationTypeException $e) {
                    $pluginConfiguration = [];
                }

                return $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['plugin-settings'][$extensionName]
                    = GeneralUtility::makeInstance(TypoScriptService::class)->convertTypoScriptArrayToPlainArray($pluginConfiguration);
            });

        return ArrayPathUtility::getPath($pluginConfiguration, $propertyPath);
    }

    public static function getPluginSettings(Registration $registration, string $propertyPath = null): mixed
    {
        return self::getPluginConfiguration($registration, 'settings');
    }
}
