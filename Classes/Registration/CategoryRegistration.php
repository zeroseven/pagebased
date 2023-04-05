<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class CategoryRegistration extends AbstractObjectRegistration
{
    protected ?string $iconIdentifier = null;

    public function getIconIdentifier(bool $hideInMenu = null): string
    {
        return ($this->iconIdentifier ?? 'apps-pagetree-page-content-from-page') . ($hideInMenu === true ? '-hideinmenu' : '');
    }

    public function setIconIdentifier(string $iconIdentifier): self
    {
        $this->iconIdentifier = $iconIdentifier;
        return $this;
    }

    public static function create(...$arguments): self
    {
        return GeneralUtility::makeInstance(self::class, ...$arguments);
    }
}
