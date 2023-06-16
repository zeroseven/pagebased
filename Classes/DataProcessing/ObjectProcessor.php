<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\DataProcessing;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Exception\ValueException;
use Zeroseven\Rampage\Utility\CastUtility;
use Zeroseven\Rampage\Utility\ObjectUtility;
use Zeroseven\Rampage\Utility\RootLineUtility;
use Zeroseven\Rampage\Utility\SettingsUtility;

class ObjectProcessor implements DataProcessorInterface
{
    /** @throws ValueException | TypeException */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array
    {
        $registrationIdentifiers = CastUtility::array($cObj->stdWrapValue('registration', $processorConfiguration, null) ?? $cObj->stdWrapValue('registration.', $processorConfiguration));
        $uid = $cObj->stdWrapValue('uid', $processorConfiguration, null) ?? RootLineUtility::getCurrentPage();

        if (empty($registrationIdentifiers)) {
            throw new ValueException('Define one or more registration identifiers.', 1623157649);
        }

        if (
            $uid && ($registration = ObjectUtility::isObject()) && in_array($registration->getIdentifier(), $registrationIdentifiers, true)
            && ($object = $registration->getObject()->getRepositoryClass()->findByUid($uid, true))
        ) {
            if ($key = $cObj->stdWrapValue('as', $processorConfiguration, null)) {
                $processedData[$key] = $object;
            } else {
                $processedData['object'] = $object;
                $processedData[strtolower($registration->getObject()->getName())] = $object; // Alias
            }

            if (!isset($processedData['registration'])) {
                $processedData['registration'] = $registration;
            }

            if (!isset($processedData['settings'])) {
                $processedData['settings'] = SettingsUtility::getPluginConfiguration($registration, 'settings');
            }
        }

        return $processedData;
    }
}
