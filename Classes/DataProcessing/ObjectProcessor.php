<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\DataProcessing;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Zeroseven\Rampage\Exception\ValueException;
use Zeroseven\Rampage\Registration\RegistrationService;

class ObjectProcessor implements DataProcessorInterface
{
    /** @throws ValueException */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array
    {
        $registrationIdentifier = $cObj->stdWrapValue('registration', $processorConfiguration, '');
        $registration = RegistrationService::getRegistrationByIdentifier($registrationIdentifier);

        if ($registration === null) {
            $validIdentifier = array_map(static fn($registration) => '"' . $registration->getIdentifier() . '"', RegistrationService::getRegistrations());

            throw new ValueException(sprintf('Registration not found, or empty "registration" configuration in %s. Use one of the following identifier %s', self::class, implode(', ', $validIdentifier)), 1623157889);
        }

        $uid = $cObj->stdWrapValue('uid', $processorConfiguration, null) ??
            (($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController ? $GLOBALS['TSFE']->id : null);

        if ($uid && $object = $registration->getObject()->getRepositoryClass()->findByUid($uid, true)) {
            if ($key = $cObj->stdWrapValue('as', $processorConfiguration, null)) {
                $processedData[$key] = $object;
            } else {
                $processedData['object'] = $object;
                $processedData[strtolower($registration->getObject()->getName())] = $object; // Alias
            }
        }

        return $processedData;
    }
}
