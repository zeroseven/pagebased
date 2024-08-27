<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Backend\TCA;

use Zeroseven\Pagebased\Domain\Model\AbstractObject;
use Zeroseven\Pagebased\Registration\RegistrationService;

class DisplayCondition
{
    public function hasChildRecords(array $params): bool
    {
        if (
            ($identifier = $params['conditionParameters'][0] ?? null)
            && ($registration = RegistrationService::getRegistrationByIdentifier($identifier))
            && ($repository = $registration->getObject()->getRepositoryClass())
            && ($demand = $registration->getObject()->getDemandClass())
            && ($objects = $repository->findByDemand($demand->setIncludeChildObjects(true)))
        ) {
            return count(array_filter($objects->toArray(), static fn(AbstractObject $object) => $object->isChildObject())) > 0;
        }

        return false;
    }
}
