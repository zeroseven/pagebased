<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Zeroseven\Rampage\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Utility\ObjectUtility;
use Zeroseven\Rampage\Utility\RootLineUtility;

class CategoryRedirect implements MiddlewareInterface
{
    protected const REDIRECT_PARAMETER = '_redirected';

    protected function buildRedirectResponse(int $startPage, Registration $registration): ?ResponseInterface
    {
        if ($listPlugin = RootLineUtility::findListPlugin($registration, $startPage, false)) {
            $pid = (int)($listPlugin['pid'] ?? 0);
            $uid = (int)($listPlugin['uid'] ?? 0);
            $controllerName = str_replace('Controller', '', GeneralUtility::makeInstance(ReflectionClass::class, $registration->getObject()->getControllerClassName())->getShortName());
            $controllerArguments = ['category' => $startPage, ObjectDemandInterface::PROPERTY_CONTENT_ID => $uid];

            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $uriBuilder->reset()
                ->setTargetPageUid($pid)
                ->setSection('c' . $uid)
                ->setCreateAbsoluteUri(true)
                ->setArguments([self::REDIRECT_PARAMETER => 1]);

            $uri = $uriBuilder->uriFor('List', $controllerArguments, $controllerName, $registration->getExtensionName(), 'List');

            return GeneralUtility::makeInstance(RedirectResponse::class, $uri, 307, ['X-Redirect-By' => 'rampage']);
        }

        return null;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return
            empty($request->getQueryParams()[self::REDIRECT_PARAMETER] ?? null) // Reduce multiple redirects
            && ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController
            && ($row = $GLOBALS['TSFE']->page ?? null)
            && ($row['rampage_redirect_category'] ?? null)
            && ($uid = $GLOBALS['TSFE']->id ?? $row['uid'] ?? null)
            && ($registration = ObjectUtility::isCategory($uid, $row))
            && ($redirectResponse = $this->buildRedirectResponse($uid, $registration))
                ? $redirectResponse
                : $handler->handle($request);
    }
}
