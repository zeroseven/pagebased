<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Imaging\IconProvider;

use InvalidArgumentException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;
use Zeroseven\Rampage\Registration\RegistrationService;

class AppIconProvider implements IconProviderInterface
{
    public function prepareIconMarkup(Icon $icon, array $options = []): void
    {
        $icon->setMarkup($this->generateMarkup($icon, $options));
    }

    protected function generateMarkup(Icon $icon, array $options): string
    {
        $registration = RegistrationService::getRegistrationByIdentifier($options['registration'] ?? '');

        if ($registration === null) {
            $validIdentifier = array_map(static fn($registration) => '"' . $registration->getIdentifier() . '"', RegistrationService::getRegistrations());

            throw new InvalidArgumentException('[' . $icon->getIdentifier() . '] Registration not found. Define the key "registration" in the icon options: ' . implode(', ', $validIdentifier), 1620146667);
        }

        $image = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g' . ($options['hideInMenu'] ?? false ? '' : ' opacity=".5"') . '><path fill="#EFEFEF" d="M2 0v16h12V4l-4-4H2z"/><path fill="#FFF" d="M10 4V0l4 4h-4z" opacity=".65"/><path fill="#212121" d="M13 5v5L9 5h4z" opacity=".2"/><path fill="#999" d="M2 0v16h12V4l-4-4H2zm1 1h6v4h4v10H3V1zm7 .4L12.6 4H10V1.4z"/><text x="8" y="12" style="font: normal bold 8px sans-serif;text-anchor: middle;">'
            . strtoupper(substr($registration->getObject()->getName(), 0, 1))
            . '</text></g></svg>';

        return '<img src="data:image/svg+xml;base64,' . base64_encode($image) . '" width="' . $icon->getDimension()->getWidth() . '" height="' . $icon->getDimension()->getHeight() . '" alt="" />';
    }
}
