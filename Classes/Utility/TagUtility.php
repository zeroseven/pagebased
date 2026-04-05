<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\LanguageAspect;
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
                // Resolve the category doktype for this specific repository to avoid matching
                // category pages from a different registration in multi-registration installations.
                $registrationDoktype = RegistrationService::getRegistrationByRepository($repository)
                    ?->getCategory()
                    ?->getDocumentType();

                $currentPage = RootLineUtility::getCurrentPage();
                $pagesAbove = RootLineUtility::collectPagesAbove($currentPage, true);

                // Find the first category page in the rootline that belongs to this registration.
                // Fall back to any registered category doktype when the registration cannot be resolved.
                $categoryUid = null;
                foreach ($pagesAbove as $page) {
                    if (!isset($page['doktype'])) {
                        continue;
                    }
                    $doktype = (int)$page['doktype'];
                    $matches = $registrationDoktype !== null
                        ? $doktype === $registrationDoktype
                        : RegistrationService::getRegistrationByCategoryDocumentType($doktype) !== null;

                    if ($matches) {
                        $categoryUid = (int)$page['uid'];
                        break;
                    }
                }

                if ($categoryUid) {
                    $demand->{'setCategory'}($categoryUid);
                } else {
                    // No category found in rootline → return null to avoid showing all tags
                    return null;
                }
            }
        }

        // Override language
        if ($languageUid !== null) {
            $querySettings = $repository->getDefaultQuerySettings();

            if ($languageUid === -1) {
                $querySettings->setRespectSysLanguage(false);
            } else {
                $languageAspect = new LanguageAspect($languageUid, $languageUid, LanguageAspect::OVERLAYS_MIXED);
                $querySettings->setLanguageAspect($languageAspect);
            }

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
