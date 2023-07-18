<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\EventListener;

use ReflectionClass;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Pagebased\Registration\Event\BeforeStoreRegistrationEvent;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationPropertyInterface;
use Zeroseven\Pagebased\Utility\SettingsUtility;

class CheckExtensionConfigurationEvent
{
    protected ?Registration $registration;

    protected function logOnConsole(string $message): void
    {
        Environment::isCli()
        && Environment::getContext()->isDevelopment()
        && DebugUtility::debug($message);
    }

    protected function logRegistrationUpdate(string $property, RegistrationPropertyInterface $registrationProperty): void
    {
        $this->logOnConsole(sprintf('Override property "%s" in "%s" for extension "%s".', $property, (new ReflectionClass($registrationProperty))->getShortName(), $this->registration->getExtensionName()));
    }

    protected function createExtensionConfigurationTemplate(): void
    {
        $path = sprintf('%s/%s/ext_conf_template.txt', Environment::getExtensionsPath(), $this->registration->getExtensionName());

        if (!@file_exists($path)) {
            GeneralUtility::writeFile($path, trim('
# Auto generated file. ( ' . self::class . ' )
registration {
    object {
        # cat=object/enable/10; type=options[default=,enable=1,disable=0]; label=Enable/disable Tags for objects
        tags =
        # cat=object/enable/20; type=options[default=,enable=1,disable=0]; label=Enable/disable Top for objects
        top =
        # cat=object/enable/30; type=options[default=,enable=1,disable=0]; label=Enable/disable relations between objects
        relations =
        # cat=object/enable/40; type=string; label=Topics;Comma separated list of topic storage page ids
        topicPageIds =
        # cat=object/enable/50; type=string; label=Contacts;Comma separated list of contact storage page ids
        contactPageIds =
        # cat=object/enable/60; type=string; label=Object overlay icon identifier
        overlayIconIdentifier =
    }
    category {
        # cat=category/enable/20; type=string; label=Category icon identifier
        iconIdentifier =
    }
    listPlugin {
        # cat=listPlugin/enable/20; type=string; label=List icon identifier
        iconIdentifier =
    }
    filterPlugin {
        # cat=filterPlugin/enable/20; type=string; label=Filter icon identifier
        iconIdentifier =
    }
}
            '));
        }
    }

    protected function overrideProperties(RegistrationPropertyInterface $registrationProperty, array $configuration): void
    {
        foreach ($configuration as $key => $value) {
            if ($value !== '' || (is_array($value) && count($value))) {
                foreach (['set' . ucfirst($key), 'add' . ucfirst($key)] as $method) {
                    if (method_exists($registrationProperty, $method)) {
                        $registrationProperty->$method($value);

                        $this->logRegistrationUpdate($key, $registrationProperty);
                        break;
                    }
                }

                if (MathUtility::canBeInterpretedAsInteger($value)) {
                    if ((int)$value === 1 && method_exists($registrationProperty, $method = 'enable' . ucfirst($key))) {
                        $registrationProperty->$method();

                        $this->logRegistrationUpdate($key, $registrationProperty);
                    }

                    if ((int)$value === 0 && method_exists($registrationProperty, $method = 'disable' . ucfirst($key))) {
                        $registrationProperty->$method();

                        $this->logRegistrationUpdate($key, $registrationProperty);
                    }
                }
            }
        }
    }

    public function __invoke(BeforeStoreRegistrationEvent $event)
    {
        $this->registration = $event->getRegistration();
        $this->createExtensionConfigurationTemplate();

        $overrides = [
            [$this->registration->getObject(), SettingsUtility::getExtensionConfiguration($this->registration, 'registration.object')],
            [$this->registration->getCategory(), SettingsUtility::getExtensionConfiguration($this->registration, 'registration.category')],
            [$this->registration->getListPlugin(), SettingsUtility::getExtensionConfiguration($this->registration, 'registration.listPlugin')],
            [$this->registration->getFilterPlugin(), SettingsUtility::getExtensionConfiguration($this->registration, 'registration.filterPlugin')]
        ];

        foreach ($overrides as $override) {
            if (!empty($override[0]) && !empty($override[1])) {
                $this->overrideProperties($override[0], $override[1]);
            }
        }
    }
}
