<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Pagebased\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Pagebased\Domain\Repository\RepositoryInterface;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;

class TagUtility
{
    public static function collectTagsFromQueryResult(QueryResultInterface $objects): array
    {
        $tags = [];
        foreach ($objects->toArray() as $object) {
            foreach ($object->getTags() ?? [] as $tag) {
                if (!in_array($tag, $tags, true)) {
                    $tags[] = $tag;
                }
            }
        }

        sort($tags, SORT_STRING);

        return $tags;
    }

    public static function getTags(ObjectDemandInterface $demand, RepositoryInterface $repository, bool $ignoreTagsFromDemand = null, int $languageUid = null): ?array
    {
        // Ensure the demand is filtered by the current blog instance (category)
        if (!$demand->{'getCategory'}()) {
            // Automatically determine the current blog category from the current page context
            $currentPage = RootLineUtility::getCurrentPage();
            $pagesAbove = RootLineUtility::collectPagesAbove($currentPage, true);

            // Find the blog category page (doktype 93) in the rootline
            $blogCategoryUid = null;
            foreach ($pagesAbove as $page) {
                if (isset($page['doktype']) && (int)$page['doktype'] === 93) {
                    $blogCategoryUid = (int)$page['uid'];
                    break;
                }
            }

            if ($blogCategoryUid) {
                $demand->{'setCategory'}($blogCategoryUid);
            } else {
                // If no blog category found, return null to avoid showing all tags
                return null;
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

        // Find objects and return their tags
        if ($objects = $repository->findByDemand($ignoreTagsFromDemand === true ? $demand->setTags(null) : $demand)) {
            return self::collectTagsFromQueryResult($objects);
        }

        return null;
    }

        // Find objects and return their tags
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
