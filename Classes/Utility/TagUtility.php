<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Rampage\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Rampage\Domain\Repository\RepositoryInterface;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

class TagUtility
{
    public static function collectTagsFromQueryResult(QueryResultInterface $objects): array
    {
        $tags = [];
        foreach ($objects->toArray() ?? [] as $object) {
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

    public static function getTagsByDemand(ObjectDemandInterface $demand, int $rootPageUid = null, bool $ignoreTagsFromDemand = null, int $languageUid = null): ?array
    {
        if (($registration = RegistrationService::getRegistrationByDemandClass($demand)) && ($repository = $registration->getObject()->getRepositoryClass()) instanceof RepositoryInterface) {
            return self::getTags($demand, $repository, $rootPageUid, $ignoreTagsFromDemand, $languageUid);
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
