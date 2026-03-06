<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Pagebased\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Pagebased\Domain\Repository\AbstractObjectRepository;
use Zeroseven\Pagebased\Domain\Repository\RepositoryInterface;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;

class TagUtility
{
    public static function collectTagsFromQueryResult(QueryResultInterface $objects): array
    {
        $tagMap = [];
        foreach ($objects->toArray() as $object) {
            foreach ($object->getTags() ?? [] as $tag) {
                $tagMap[$tag] = true;
            }
        }

        $tags = array_keys($tagMap);
        sort($tags, SORT_STRING);

        return $tags;
    }

    /**
     * Collects distinct sorted tags from raw CSV strings (e.g. from findTagStrings()).
     * Much cheaper than collectTagsFromQueryResult() as it avoids Extbase hydration.
     *
     * @param string[] $tagStrings Raw comma-separated tag strings, one per row.
     * @return string[]
     */
    public static function collectTagsFromStrings(array $tagStrings): array
    {
        $tagMap = [];
        foreach ($tagStrings as $csv) {
            foreach (GeneralUtility::trimExplode(',', $csv, true) as $tag) {
                $tagMap[$tag] = true;
            }
        }

        $tags = array_keys($tagMap);
        sort($tags, SORT_STRING);

        return $tags;
    }

    public static function getTags(ObjectDemandInterface $demand, RepositoryInterface $repository, bool $ignoreTagsFromDemand = null, int $languageUid = null): ?array
    {
        // Override language
        if ($languageUid !== null) {
            $querySettings = $repository->getDefaultQuerySettings();

            $languageUid === -1
                ? $querySettings->setRespectSysLanguage(false)
                : $querySettings->setLanguageUid($languageUid);

            $repository->setDefaultQuerySettings($querySettings);
        }

        // Use a lightweight tag-only query when supported (avoids full Extbase object hydration).
        // Skip this path when a non-default language is active: findTagStrings() uses raw DBAL
        // and does not apply language overlays. We check both the explicit $languageUid parameter
        // and the current TYPO3 Context language so implicit callers (e.g. AbstractObjectController)
        // are also protected when operating in a non-default site language.
        $contextLanguageUid = (int)GeneralUtility::makeInstance(Context::class)
            ->getPropertyFromAspect('language', 'id', 0);
        if ($repository instanceof AbstractObjectRepository && $languageUid === null && $contextLanguageUid === 0) {
            $tagDemand = $ignoreTagsFromDemand === true ? $demand->setTags(null) : $demand;
            $tagStrings = $repository->findTagStrings($tagDemand);

            return $tagStrings !== [] ? self::collectTagsFromStrings($tagStrings) : null;
        }

        // Fallback: load full objects and extract tags in PHP
        if ($objects = $repository->findByDemand($ignoreTagsFromDemand === true ? $demand->setTags(null) : $demand)) {
            return self::collectTagsFromQueryResult($objects);
        }

        return null;
    }

    public static function getTagsByDemand(ObjectDemandInterface $demand, bool $ignoreTagsFromDemand = null, int $languageUid = null): ?array
    {
        if (($registration = RegistrationService::getRegistrationByDemand($demand)) && ($repository = $registration->getObject()->getRepositoryClass()) instanceof RepositoryInterface) {
            return self::getTags($demand, $repository, $ignoreTagsFromDemand, $languageUid);
        }

        return null;
    }

    public static function getTagsByRegistration(Registration $registration, bool $ignoreTagsFromDemand = null, int $languageUid = null): ?array
    {
        if (($demand = $registration->getObject()->getDemandClass()) instanceof ObjectDemandInterface && ($repository = $registration->getObject()->getRepositoryClass()) instanceof RepositoryInterface) {
            return self::getTags($demand, $repository, $ignoreTagsFromDemand, $languageUid);
        }

        return null;
    }
}
