<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Imaging\IconProvider;

use TYPO3\CMS\Core\Imaging\Icon;
use Zeroseven\Rampage\Registration\Registration;

class OverlayIconProvider extends AbstractIconProvider
{
    protected function createImage(Icon $icon, array $options, Registration $registration): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 11 11"><path fill="#666" d="M1 1h10v10H1z"/><path fill="#FFF" d="M2 2h8v8H2z"/><text x="6" y="8.5" style="fill:#222;font:normal bold 7px arial;text-anchor:middle;">'
            . strtoupper(substr($registration->getObject()->getName(), 0, 1))
            . '</text></svg>';
    }
}
