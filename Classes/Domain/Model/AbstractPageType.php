<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Domain\Model;

use TYPO3\CMS\Core\Type\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractPageType extends AbstractPageObject implements PageTypeInterface
{
    /** @throws Exception */
    public function setDocumentType(int $documentType): self
    {
        if (method_exists(self::class, 'getType') && $documentType !== ($type = GeneralUtility::makeInstance(self::class)::getType())) {
            throw new Exception(sprintf('The doctype must be %d', $type), 1675944147);
        }

        return parent::setDocumentType($documentType);
    }
}
