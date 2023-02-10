<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Exception\ValueException;

abstract class AbstractPageType extends AbstractPageObject implements PageTypeInterface
{
    /** @throws ValueException */
    public function setDocumentType(int $documentType): self
    {
        if (method_exists(self::class, 'getType') && $documentType !== ($type = GeneralUtility::makeInstance(self::class)::getType())) {
            throw new ValueException(sprintf('The doctype must be %d', $type), 1675944147);
        }

        return parent::setDocumentType($documentType);
    }
}
