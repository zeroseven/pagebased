<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Middleware;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Routing\RouteNotFoundException;
use TYPO3\CMS\Core\Routing\RouteResultInterface;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Pagebased\Event\Rss\RssFeedEvent;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;

class RssFeed implements MiddlewareInterface
{
    private const URL_SUFFIX = '/-/rss.xml';
    private const TABLE_NAME = 'tt_content';

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
            return GeneralUtility::makeInstance(FlexFormService::class)->convertFlexFormContentToArray($flexForm)['settings'] ?? [];
        }

        return [];
    }

    protected function getObjects(Registration $registration, array $settings, SiteLanguage $language): ?QueryResultInterface
    {
        $demand = $registration->getObject()->getDemandClass()->setParameterArray($settings);
        $repository = $registration->getObject()->getRepositoryClass();

        if ($languageId = $language->getLanguageId()) {
            $querySettings = $repository->getDefaultQuerySettings();
            $querySettings->setLanguageUid($languageId);
            $repository->setDefaultQuerySettings($querySettings);
        }

        return $repository->findByDemand($demand);
    }

    protected function getPid(ServerRequestInterface $request, RouteResultInterface $routing): ?int
    {
        if ($site = $request->getAttribute('site')) {
            $path = $request->getUri()->getPath() === self::URL_SUFFIX ? '/'
                : str_replace(self::URL_SUFFIX, '/', $routing->offsetGet('tail'));
            $routing->offsetSet('tail', $path);

            try {
                $arguments = $site->getRouter()->matchRequest($request->withUri($request->getUri()->withPath($path)), $routing);

                return $arguments->getPageId();
            } catch (RouteNotFoundException $e) {
            }
        }

        return null;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            str_ends_with($request->getUri()->getPath(), self::URL_SUFFIX)
            && ($routing = $request->getAttribute('routing')) instanceof RouteResultInterface
            && ($language = $routing->getLanguage()) instanceof SiteLanguage
            && ($pid = $this->getPid($request, $routing))
        ) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

            $CTypes = array_filter(array_map(static function (Registration $registration) use ($queryBuilder) {
                return $registration->hasListPlugin() ? $queryBuilder->quote($registration->getListPlugin()->getCType($registration)) : null;
            }, RegistrationService::getRegistrations() ?? []));

            try {
                $content = empty($CTypes) ? null : $queryBuilder->select('*')
                    ->from(self::TABLE_NAME)
                    ->where(
                        $queryBuilder->expr()->in($GLOBALS['TCA'][self::TABLE_NAME]['ctrl']['type'], $CTypes),
                        $queryBuilder->expr()->in($GLOBALS['TCA'][self::TABLE_NAME]['ctrl']['languageField'], [-1, $language->getLanguageId()]),
                        $queryBuilder->expr()->eq('pid', $pid),
                    )
                    ->orderBy($GLOBALS['TCA'][self::TABLE_NAME]['ctrl']['sortby'])
                    ->setMaxResults(1)
                    ->execute()
                    ->fetchAllAssociative()[0] ?? null;

                if (
                    $content
                    && ($CType = $content[$GLOBALS['TCA'][self::TABLE_NAME]['ctrl']['type'] ?? ''])
                    && ($registration = $this->getRegistrationByCType($CType))
                    && ($settings = $this->getPluginSettings($content))
                    && ($objects = $this->getObjects($registration, $settings, $language))
                ) {
                    $rssFeed = GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new RssFeedEvent($registration, $request, $settings, $content, $objects))->render();

                    return GeneralUtility::makeInstance(HtmlResponse::class, trim('<?xml version="1.1" encoding="utf-8"?>' . $rssFeed), 200, [
                        'Content-Type' => 'application/rss+xml; charset=utf-8',
                        'X-Robots-Tag' => 'noindex',
                        'X-Typo3-Extension' => 'pagebased',
                        'X-Xml-Identifier' => md5($registration->getIdentifier() . ($content['uid'] ?? '')),
                        'X-Xml-Items' => $objects->count()
                    ]);
                }
            } catch (DBALException|Exception $e) {
            }

            return GeneralUtility::makeInstance(RedirectResponse::class, str_replace(self::URL_SUFFIX, '/', $request->getUri()->getPath()), 303);
        }

        return $handler->handle($request);
    }
}
