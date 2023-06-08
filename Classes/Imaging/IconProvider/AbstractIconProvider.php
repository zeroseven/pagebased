<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Imaging\IconProvider;

use InvalidArgumentException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

abstract class AbstractIconProvider implements IconProviderInterface
{
    abstract protected function createImage(Icon $icon, array $options, Registration $registration): string;

    protected function generateMarkup(Icon $icon, array $options, Registration $registration): string
    {
        return '<img src="data:image/svg+xml;base64,' . base64_encode($this->createImage($icon, $options, $registration)) . '" width="' . $icon->getDimension()->getWidth() . '" height="' . $icon->getDimension()->getHeight() . '" alt="" />';
    }

    public function prepareIconMarkup(Icon $icon, array $options = []): void
    {
        $registration = RegistrationService::getRegistrationByIdentifier($options['registration'] ?? '');

        if ($registration === null) {
            $validIdentifier = array_map(static fn($registration) => '"' . $registration->getIdentifier() . '"', RegistrationService::getRegistrations());

            throw new InvalidArgumentException('[' . $icon->getIdentifier() . '] Registration not found. Define the key "registration" in the icon options: ' . implode(', ', $validIdentifier), 1620146667);
        }

        $icon->setMarkup($this->generateMarkup($icon, $options, $registration));
        $icon->setAlternativeMarkup('inline', $this->createImage($icon, $options, $registration));
    }
}
