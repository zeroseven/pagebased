<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\ViewHelpers\Pagination\Link;

use Zeroseven\Rampage\Pagination\Pagination;

final class PrevViewHelper extends AbstractLinkViewHelper
{
    protected function getTargetStage(Pagination $pagination): ?int
    {
        return $pagination->getPreviousStage();
    }
}
