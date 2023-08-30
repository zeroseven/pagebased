<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Mvc\RequestInterface as ExtbaseRequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use Zeroseven\Pagebased\Registration\Registration;

class RequestUtility
{
    protected static function getExtbaseRequestCache(Registration $registration): ?ExtbaseRequestInterface
    {
        return $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['cache']['extbaseRequest'][$registration->getIdentifier()] ?? null;
    }

    protected static function setExtbaseRequestCache(Registration $registration, ExtbaseRequestInterface $request): ExtbaseRequestInterface
    {
        return $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['cache']['extbaseRequest'][$registration->getIdentifier()] = $request;
    }

    public static function getExtbaseRequest(Registration $registration, ServerRequestInterface $serverRequest = null): ?ExtbaseRequestInterface
    {
        if (($request = self::getExtbaseRequestCache($registration)) instanceof ExtbaseRequestInterface) {
            return $request;
        }

        if ($serverRequest === null) {
            $serverRequest = self::getServerRequest();
        }

        if ($serverRequest instanceof ServerRequestInterface) {
            $pluginName = $registration->getListPlugin()?->getType() ?? $registration->getFilterPlugin()?->getType() ?? 'default';
            $bootstrapInitialization = GeneralUtility::makeInstance(Bootstrap::class)?->initialize([
                'extensionName' => GeneralUtility::underscoredToUpperCamelCase($registration->getExtensionName()),
                'pluginName' => ucfirst($pluginName),
                'vendorName' => strtok($registration->getObject()->getClassName(), '\\'),
            ], $serverRequest);

            if (($request = GeneralUtility::makeInstance(RequestBuilder::class)?->build($bootstrapInitialization)) instanceof ExtbaseRequestInterface) {
                return self::setExtbaseRequestCache($registration, $request);
            }
        }

        return null;
    }

    public static function getServerRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }
}
