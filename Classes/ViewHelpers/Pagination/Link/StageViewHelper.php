<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ViewHelpers\Pagination\Link;

use Zeroseven\Pagebased\Pagination\Pagination;

final class StageViewHelper extends AbstractPaginationLinkViewHelper
{
    protected const STAGE_ACTIVE_ATTRIBUTE = 'data-stage-active';

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('index', 'int', 'Index of the target stage', true);
        $this->registerArgument('dataAttributes', 'bool', 'Set data attributes, if the linked stage is selected', false, true);

    }

    protected function getTargetStage(Pagination $pagination): ?int
    {
        $stage = (int)($this->arguments['index'] ?? 0);

        if (($this->arguments['dataAttributes'] ?? false) && $stage === $pagination->getSelectedStage()) {
            $this->tag->addAttribute(self::STAGE_ACTIVE_ATTRIBUTE, 'true');
        }

        return $stage;
    }
}
