<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ViewHelpers\Condition;

use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Utility\ObjectUtility;

class IsObjectViewHelper extends AbstractConditionViewHelper
{
    protected function detectRegistration(): ?Registration
    {
        return ObjectUtility::isObject();
    }
}
