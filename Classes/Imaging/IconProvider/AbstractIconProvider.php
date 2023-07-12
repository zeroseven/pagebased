<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Imaging\IconProvider;

use InvalidArgumentException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;
use Zeroseven\Pagebased\Exception\ValueException;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;

abstract class AbstractIconProvider implements IconProviderInterface
{
    abstract protected function createImage(Icon $icon, array $options, Registration $registration): string;

    protected function generateMarkup(Icon $icon, array $options, Registration $registration): string
    {
        return '<img src="data:image/svg+xml;base64,' . base64_encode($this->createImage($icon, $options, $registration)) . '" width="' . $icon->getDimension()->getWidth() . '" height="' . $icon->getDimension()->getHeight() . '" alt="" />';
    }

    public function prepareIconMarkup(Icon $icon, array $options = []): void
    {
        try {
            $registration = RegistrationService::getRegistrationByIdentifier($options['registration'] ?? '');
        } catch (ValueException $e) {
            throw new InvalidArgumentException('[' . $icon->getIdentifier() . '] Registration not found: ' . $e->getMessage(), 1620146666);
        }

        $icon->setMarkup($this->generateMarkup($icon, $options, $registration));
        $icon->setAlternativeMarkup('inline', $this->createImage($icon, $options, $registration));
    }
}
