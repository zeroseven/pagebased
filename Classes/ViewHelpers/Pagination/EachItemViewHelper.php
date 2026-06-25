<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ViewHelpers\Pagination;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\ViewHelpers\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Zeroseven\Pagebased\Pagination\Iterator;
use Zeroseven\Pagebased\ViewHelpers\PaginationViewHelper;

final class EachItemViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('as', 'string', 'The name of the iteration variable', false, 'item');
        $this->registerArgument('iteration', 'string', 'The name of the variable to store iteration information (index, cycle, isFirst, isLast, isEven, isOdd)');
    }

    /** @throws Exception */
    public function render(): string
    {
        $variableProvider = $this->renderingContext->getVariableProvider();
        $as = $this->arguments['as'] ?? 'item';
        $iteration = $this->arguments['iteration'] ?? 'itemIteration';
        $output = '';

        if (!$variableProvider->exists(PaginationViewHelper::PAGINATION_VARIABLE_IDENTIFIER) && !$variableProvider->exists(EachStageViewHelper::STAGE_VARIABLE_IDENTIFIER)) {
            throw new Exception(sprintf('The ViewHelper "%s" may only be used inside "%s" or "%s".', self::class, EachStageViewHelper::class, PaginationViewHelper::class), 1677234321);
        }

        if ($items = ($variableProvider->get(EachStageViewHelper::STAGE_VARIABLE_IDENTIFIER) ?? $variableProvider->get(PaginationViewHelper::PAGINATION_VARIABLE_IDENTIFIER))->getItems()) {
            $iterator = GeneralUtility::makeInstance(Iterator::class, count($items));

            foreach ($items as $item) {
                $variableProvider->add($iteration, $iterator);
                $variableProvider->add($as, $item);

                $output .= $this->renderChildren();

                $variableProvider->remove($iteration);
                $variableProvider->remove($as);

                $iterator->count();
            }
        }

        return $output;
    }
}
