<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Domain\Model\PageTypeInterface;

class CategoryRegistration extends AbstractEntityRegistration
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

    public function getObjectType(): int
    {
        return is_subclass_of($this->className, PageTypeInterface::class)
            ? $this->className::getType()
            : 0;
    }

    public static function create(...$arguments): self
    {
        return GeneralUtility::makeInstance(self::class, ...$arguments);
    }
}
