<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Backend\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use Zeroseven\Rampage\Registration\RegistrationService;

class ObjectsElement extends AbstractFormElement
{
    public function render(): array
    {
        $pid = (int)($this->data['databaseRow']['pid'] ?? 0);
        $table = $this->data['tableName'] ?? '';

        if ($pid && $table) {
            $objects = [];
            $registrations = RegistrationService::getRegistrations();

            if ('tx_rampage_domain_model_topic' === $table) {
                foreach ($registrations as $registration) {
                    if (in_array($pid, $registration->getObject()->getTopicPageIds(), true)) {
                        $objects[] = $registration->getObject()->getTitle();
                    }
                }
            }

            if ('tx_rampage_domain_model_contact' === $table) {
                foreach ($registrations as $registration) {
                    if (in_array($pid, $registration->getObject()->getContactPageIds(), true)) {
                        $objects[] = $registration->getObject()->getTitle();
                    }
                }
            }

            return [
                'html' => '<div>' . (implode(', ', $objects) ?: '-') . '</div>'
            ];
        }

        return [];
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1683899231] = [
            'nodeName' => 'rampageObjects',
            'priority' => 100,
            'class' => self::class,
        ];
    }
}
