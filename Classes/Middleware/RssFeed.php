<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Middleware;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Routing\RouteNotFoundException;
use TYPO3\CMS\Core\Routing\RouteResultInterface;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;
use Zeroseven\Pagebased\Event\Rss\RssFeedEvent;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;

class RssFeed implements MiddlewareInterface
{
    private const CACHE_KEY = 'pagebased_rss_feed';

    private const URL_SUFFIX = '/-/rss.xml';

    private const TABLE_NAME = 'tt_content';

    public function __construct(private readonly FrontendInterface $frontend, private readonly FlexFormService $flexFormService, private readonly EventDispatcher $eventDispatcher) {}

    protected function getRegistrationByCType(string $CType): ?Registration
    {
        foreach (RegistrationService::getRegistrations() ?? [] as $registration) {
            if ($registration->hasListPlugin() && $registration->getListPlugin()->getCType($registration) === $CType) {
                return $registration;
            }
        }

        return null;
    }

    protected function getPluginSettings(array $pluginConfiguration): array
    {
        if ($flexForm = $pluginConfiguration['pi_flexform'] ?? null) {
            return $this->flexFormService->convertFlexFormContentToArray($flexForm)['settings'] ?? [];
        }

        return [];
    }

    protected function getObjects(Registration $registration, array $settings, SiteLanguage $siteLanguage): ?QueryResultInterface
    {
        $demand = $registration->getObject()->getDemandClass()->setParameterArray($settings);
        $repositoryRepository = $registration->getObject()->getRepositoryClass();

        if (($languageId = $siteLanguage->getLanguageId()) !== 0) {
            $querySettings = $repositoryRepository->getDefaultQuerySettings();
            $languageAspect = new LanguageAspect($languageId, $languageId, LanguageAspect::OVERLAYS_MIXED);
            $querySettings->setLanguageAspect($languageAspect);
            $repositoryRepository->setDefaultQuerySettings($querySettings);
        }

        return $repositoryRepository->findByDemand($demand);
    }

    protected function getPid(ServerRequestInterface $serverRequest, RouteResultInterface $routeResult): ?int
    {
        if ($site = $serverRequest->getAttribute('site')) {
            $path = $serverRequest->getUri()->getPath() === self::URL_SUFFIX ? '/'
                : str_replace(self::URL_SUFFIX, '/', $routeResult->offsetGet('tail'));
            $routeResult->offsetSet('tail', $path);

            try {
                $arguments = $site->getRouter()->matchRequest($serverRequest->withUri($serverRequest->getUri()->withPath($path)), $routeResult);

                return $arguments->getPageId();
            } catch (RouteNotFoundException) {
            }
        }

        return null;
    }

    public static function registerCache(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][self::CACHE_KEY] ??= [
            'options' => [
                'defaultLifetime' => 18000, // 5 hours
            ],
        ];
    }

    /** @throws PageNotFoundException */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (str_ends_with($request->getUri()->getPath(), self::URL_SUFFIX)) {
            if (
                ($routing = $request->getAttribute('routing')) instanceof RouteResultInterface
                && ($language = $routing->getLanguage()) instanceof SiteLanguage
                && ($pid = $this->getPid($request, $routing))
            ) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

                $CTypes = array_filter(array_map(static fn(Registration $registration): ?string => $registration->hasListPlugin() ? $queryBuilder->quote($registration->getListPlugin()->getCType($registration)) : null, RegistrationService::getRegistrations() ?? []));

                try {
                    $content = $CTypes === [] ? null : $queryBuilder->select('*')
                        ->from(self::TABLE_NAME)
                        ->where(
                            $queryBuilder->expr()->in($GLOBALS['TCA'][self::TABLE_NAME]['ctrl']['type'], $CTypes),
                            $queryBuilder->expr()->in($GLOBALS['TCA'][self::TABLE_NAME]['ctrl']['languageField'], [-1, $language->getLanguageId()]),
                            $queryBuilder->expr()->eq('pid', $pid),
                        )
                        ->orderBy($GLOBALS['TCA'][self::TABLE_NAME]['ctrl']['sortby'])
                        ->setMaxResults(1)
                        ->executeQuery()
                        ->fetchAllAssociative()[0] ?? null;

                    if (
                        $content
                        && ($CType = $content[$GLOBALS['TCA'][self::TABLE_NAME]['ctrl']['type'] ?? ''])
                        && ($registration = $this->getRegistrationByCType($CType))
                    ) {
                        $identifier = md5($registration->getIdentifier() . ($content['uid'] ?? '') . $language->getLanguageId());

                        if (empty($rssFeed = $this->frontend->get($identifier))) {
                            $settings = $this->getPluginSettings($content);
                            $objects = $this->getObjects($registration, $settings, $language);
                            $rssFeed = $this->eventDispatcher->dispatch(new RssFeedEvent($registration, $request, $settings, $content, $objects))->render();

                            $this->frontend->set($identifier, $rssFeed);
                        }

                        return GeneralUtility::makeInstance(HtmlResponse::class, trim('<?xml version="1.1" encoding="utf-8"?>' . $rssFeed), 200, [
                            'Content-Type' => 'application/rss+xml; charset=utf-8',
                            'X-Robots-Tag' => 'noindex',
                            'X-Typo3-Extension' => 'pagebased',
                            'X-Xml-Identifier' => $identifier,
                        ]);
                    }
                } catch (DBALException | Exception) {
                }
            }

            $error = GeneralUtility::makeInstance(PageAccessFailureReasons::class)->getMessageForReason(PageAccessFailureReasons::PAGE_NOT_FOUND);

            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction($request, $error, ['code' => 404]);
        }

        return $handler->handle($request);
    }
}
