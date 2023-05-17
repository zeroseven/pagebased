<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Zeroseven\Rampage\Registration\Event\AfterStoreRegistrationEvent;
use Zeroseven\Rampage\Registration\Registration;

class AddTypoScriptEvent
{
    public static function getTypoScriptPluginKey(Registration $registration): string
    {
        return 'tx_' . str_replace('_', '', strtolower($registration->getExtensionName()));
    }

    public function addTypoScriptConstants(Registration $registration): void
    {
        $pluginKey = self::getTypoScriptPluginKey($registration);

        ExtensionManagementUtility::addTypoScriptConstants('plugin.' . $pluginKey . ' {
            view {
                # cat=plugin.' . $pluginKey . '/file; type=string; label=Path to template root (FE)
                templateRootPath =
                # cat=plugin.' . $pluginKey . '/file; type=string; label=Path to template partials (FE)
                partialRootPath =
                # cat=plugin.' . $pluginKey . '/file; type=string; label=Path to template layouts (FE)
                layoutRootPath =
            }
        }');
    }

    public function addTypoScriptSetup(Registration $registration): void
    {
        $setup = [];
        $pluginKey = self::getTypoScriptPluginKey($registration);
        $resourcePath = 'EXT:' . $registration->getExtensionName() . '/Resources/';

        $copyPluginOptions = [
            'settings.list.ajaxTypeNum',
            'features.skipDefaultArguments',
            'mvc.callDefaultActionIfActionCantBeResolved'
        ];

        $setup[] = 'view {
            templateRootPaths {
                0 = ' . $resourcePath . 'Private/Templates/
                100 = {$plugin.' . $pluginKey . '.view.templateRootPath}
            }

            partialRootPaths {
                0 = ' . $resourcePath . 'Private/Partials/
                100 = {$plugin.' . $pluginKey . '.view.partialRootPath}
            }

            layoutRootPaths {
                0 = ' . $resourcePath . 'Private/Layouts/
                100 = {$plugin.' . $pluginKey . '.view.layoutRootPath}
            }
        }';

        foreach ($copyPluginOptions as $pluginOption) {
            $setup[] = $pluginOption . ' < plugin.tx_rampage.' . $pluginOption;
        }

        ExtensionManagementUtility::addTypoScriptSetup(implode("\n", array_map(static fn($v): string => 'plugin.' . $pluginKey . '.' . trim($v), $setup)));
    }

    public function __invoke(AfterStoreRegistrationEvent $event): void
    {
        $this->addTypoScriptSetup($event->getRegistration());
        $this->addTypoScriptConstants($event->getRegistration());
    }
}
