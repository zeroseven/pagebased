<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\ViewHelpers\Pagination;

use Closure;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\ViewHelpers\Exception;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use Zeroseven\Rampage\Pagination\Iterator;
use Zeroseven\Rampage\ViewHelpers\PaginationViewHelper;

final class EachItemViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('as', 'string', 'The name of the iteration variable', false, 'item');
        $this->registerArgument('iteration', 'string', 'The name of the variable to store iteration information (index, cycle, isFirst, isLast, isEven, isOdd)');
    }

    /** @throws Exception */
    public static function renderStatic(array $arguments, Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $templateVariableContainer = $renderingContext->getVariableProvider();
        $as = $arguments['as'] ?? 'item';
        $iteration = $arguments['iteration'] ?? 'itemIteration';
        $output = '';

        if (!$templateVariableContainer->exists(PaginationViewHelper::PAGINATION_VARIABLE_IDENTIFIER) && !$templateVariableContainer->exists(EachStageViewHelper::STAGE_VARIABLE_IDENTIFIER)) {
            throw new Exception(sprintf('The ViewHelper "%s" may only be used inside "%s" or "%s".', self::class, EachStageViewHelper::class, PaginationViewHelper::class), 1677234321);
        }

        if ($items = ($templateVariableContainer->get(EachStageViewHelper::STAGE_VARIABLE_IDENTIFIER) ?? $templateVariableContainer->get(PaginationViewHelper::PAGINATION_VARIABLE_IDENTIFIER))->getItems()) {
            $iterator = GeneralUtility::makeInstance(Iterator::class, count($items));

            foreach ($items as $item) {
                $templateVariableContainer->add($iteration, $iterator);
                $templateVariableContainer->add($as, $item);

                $output .= $renderChildrenClosure();

                $templateVariableContainer->remove($iteration);
                $templateVariableContainer->remove($as);

                $iterator->count();
            }
        }

        return $output;
    }
}
