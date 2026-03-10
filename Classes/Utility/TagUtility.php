<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use TYPO3\CMS\Core\Configuration\Features;
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
        // When the nonglobal-tags feature is enabled, scope tags to the current rootline category.
        if (GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('pagebased.nonglobalTags')) {
            if (!$demand->{'getCategory'}()) {
                // Automatically determine the current blog category from the current page context
                $currentPage = RootLineUtility::getCurrentPage();
                $pagesAbove = RootLineUtility::collectPagesAbove($currentPage, true);

                // Find the first registered category page in the rootline
                $blogCategoryUid = null;
                foreach ($pagesAbove as $page) {
                    if (isset($page['doktype']) && RegistrationService::getRegistrationByCategoryDocumentType((int)$page['doktype']) !== null) {
                        $blogCategoryUid = (int)$page['uid'];
                        break;
                    }
                }

                if ($blogCategoryUid) {
                    $demand->{'setCategory'}($blogCategoryUid);
                } else {
                    // No blog category found in rootline → return null to avoid showing all tags
                    return null;
                }
            }
        }

        // Override language
        if ($languageUid !== null) {
            $querySettings = $repository->getDefaultQuerySettings();

            $languageUid === -1
                ? $querySettings->setRespectSysLanguage(false)
                : $querySettings->setLanguageUid($languageUid);

            $repository->setDefaultQuerySettings($querySettings);
        }

        // Use a lightweight tag-only query when supported (avoids full Extbase object hydration)
        if ($repository instanceof AbstractObjectRepository) {
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
