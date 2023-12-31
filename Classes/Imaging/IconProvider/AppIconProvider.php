<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Imaging\IconProvider;

use TYPO3\CMS\Core\Imaging\Icon;
use Zeroseven\Pagebased\Registration\Registration;

class AppIconProvider extends AbstractIconProvider
{
    protected function createImage(Icon $icon, array $options, Registration $registration): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g' . ($options['hideInMenu'] ?? false ? ' opacity=".5"' : '') . '><path fill="#EFEFEF" d="M2 0v16h12V4l-4-4H2z"/><path fill="#FFF" d="M10 4V0l4 4h-4z" opacity=".65"/><path fill="#212121" d="M13 5v5L9 5h4z" opacity=".2"/><path fill="#999" d="M2 0v16h12V4l-4-4H2zm1 1h6v4h4v10H3V1zm7 .4L12.6 4H10V1.4z"/><text x="8" y="12" style="fill:#222;font:normal bold 8px arial;text-anchor:middle;">'
            . strtoupper(substr($options['letter'] ?? $registration->getCategory()->getTitle(), 0, 1))
            . '</text></g></svg>';
    }
}
