<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\ViewHelpers\Pagination\Link;

use Zeroseven\Rampage\Pagination\Pagination;

final class NextViewHelper extends AbstractPaginationLinkViewHelper
{
    protected function getTargetStage(Pagination $pagination): ?int
    {
        return $pagination->getNextStage();
    }
}
