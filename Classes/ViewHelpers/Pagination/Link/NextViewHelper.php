<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\ViewHelpers\Pagination\Link;

use TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper;
use TYPO3\CMS\Install\ViewHelpers\Exception;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractDemand;
use Zeroseven\Rampage\ViewHelpers\PaginationViewHelper;

class NextViewHelper extends ActionViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        // Register demand argument
        $this->registerArgument('demand', 'object', 'The demand object', true);
    }

    /** @throws Exception */
    public function render(): string
    {
        if (!$this->templateVariableContainer->exists(PaginationViewHelper::PAGINATION_VARIABLE_IDENTIFIER)) {
            throw new Exception(sprintf('The ViewHelper "%s" may only be used inside "%s".', self::class, PaginationViewHelper::class), 1677243233);
        }

        if ($nextStage = $this->templateVariableContainer->get(PaginationViewHelper::PAGINATION_VARIABLE_IDENTIFIER)->getNextStage()) {
            $overrides = $this->arguments['demand']->getDiff($this->templateVariableContainer->get('settings'), [AbstractDemand::PARAMETER_UID_LIST]);

            foreach ($overrides as $key => $value) {
                $this->arguments['arguments'][$key] = $value;
            }

            $this->arguments['arguments'][PaginationViewHelper::REQUEST_PARAMETER] = $nextStage;

        } else {
            return $this->renderChildren();
        }

        return parent::render();
    }
}
