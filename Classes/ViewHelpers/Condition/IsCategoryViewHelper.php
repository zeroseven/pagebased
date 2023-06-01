<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\ViewHelpers\Condition;

use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Utility\ObjectUtility;

class isCategoryViewHelper extends AbstractConditionViewHelper
{
    protected function detectRegistration(): ?Registration
    {
        return ObjectUtility::isCategory();
    }
}
