<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use ReflectionClass;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Rampage\Registration\Event\BeforeStoreRegistrationEvent;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationPropertyInterface;
use Zeroseven\Rampage\Utility\SettingsUtility;

class CheckExtensionConfigurationEvent
{
    protected ?Registration $registration;

    protected function logOnConsole(string $message): void
    {
        Environment::isCli() && DebugUtility::debug($message);
    }

    protected function logRegistrationUpdate(string $property, RegistrationPropertyInterface $registrationProperty): void
    {
        $this->logOnConsole(sprintf('Override property "%s" in "%s" for extension "%s".', $property, (new ReflectionClass($registrationProperty))->getShortName(), $this->registration->getExtensionName()));
    }

    protected function overrideProperties(RegistrationPropertyInterface $registrationProperty, array $configuration): void
    {
        foreach ($configuration ?? [] as $key => $value) {
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
