<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Domain\Model\PageTypeInterface;

final class CategoryRegistration extends AbstractRegistrationEntityProperty
{
    protected ?string $iconIdentifier = null;
    protected int $documentType = 0;

    public function getIconIdentifier(bool $hideInMenu = null): string
    {
        return $this->iconIdentifier . ($hideInMenu === true ? '-hideinmenu' : '');
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

    public function getDocumentType(): int
    {
        return $this->documentType;
    }

    public function setDocumentType(int $documentType): self
    {
        $this->documentType = $documentType;
        return $this;
    }

    public static function create(string $title): self
    {
        return GeneralUtility::makeInstance(self::class, $title);
    }
}
