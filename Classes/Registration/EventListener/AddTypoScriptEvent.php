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

            registration {
                identifier = ' . $registration->getIdentifier() . '
                category.documentType = ' . $registration->getCategory()->getDocumentType() . '
            }

            persistence {
                # cat=plugin.' . $pluginKey . '/links; type=string; label=Default storage PID
                storagePid = ' . implode(',', array_unique(array_merge($registration->getObject()->getTopicPageIds(), $registration->getObject()->getContactPageIds()), SORT_NUMERIC)) . '
            }
        }');
    }

    public function addTypoScriptSetup(Registration $registration): void
    {
        $pluginKey = self::getTypoScriptPluginKey($registration);
        $resourcePath = 'EXT:' . $registration->getExtensionName() . '/Resources/';

        // Plugin settings
        ExtensionManagementUtility::addTypoScriptSetup('plugin.' . $pluginKey . '{
            settings {
                list.ajaxTypeNum = {$plugin.tx_rampage.settings.list.ajaxTypeNum}
                registration {
                    identifier = {$plugin.' . $pluginKey . '.registration.identifier}
                    category.documentType = {$plugin.' . $pluginKey . '.registration.category.documentType}
                }
            }
            features.skipDefaultArguments = 1
            mvc.callDefaultActionIfActionCantBeResolved = 1
            view {
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
            }
            persistence {
                storagePid = {$plugin.' . $pluginKey . '.persistence.storagePid}
            }
        }');
    }

    public function __invoke(AfterStoreRegistrationEvent $event): void
    {
        $this->addTypoScriptSetup($event->getRegistration());
        $this->addTypoScriptConstants($event->getRegistration());
    }
}
