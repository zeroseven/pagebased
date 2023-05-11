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
use Zeroseven\Rampage\Pagination\Pagination;
use Zeroseven\Rampage\ViewHelpers\PaginationViewHelper;

class EachStageViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public const STAGE_VARIABLE_IDENTIFIER = '🤬-9a8c2b9d518bc163e99611fbacea63b2'; // md5('stage');

    protected $escapeOutput = false;

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('selected', 'bool', 'Enter only the selected stage');
        $this->registerArgument('active', 'bool', 'Loop the active stages only');
        $this->registerArgument('inactive', 'bool', 'Loop the inactive stages only');
        $this->registerArgument('as', 'string', 'The name of the iteration variable');
        $this->registerArgument('iteration', 'string', 'The name of the variable to store iteration information (index, cycle, isFirst, isLast, isEven, isOdd)');
    }

    /** @throws Exception */
    public static function renderStatic(array $arguments, Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $selected = (bool)($arguments['selected'] ?? false);
        $active = (bool)($arguments['active'] ?? false);
        $inactive = (bool)($arguments['inactive'] ?? false);
        $as = (empty($as = $arguments['as'] ?? null) || $as === self::STAGE_VARIABLE_IDENTIFIER) ? null : $as;
        $iteration = $arguments['iteration'] ?? 'stageIteration';

        if (((int)$selected + (int)$active + (int)$inactive) > 1) {
            throw new Exception('You can only activate one filter in EachStageViewHelper. Either "selected" or "active" or "inactive"', 1677232999);
        }

        $templateVariableContainer = $renderingContext->getVariableProvider();

        if (!$templateVariableContainer->exists(PaginationViewHelper::PAGINATION_VARIABLE_IDENTIFIER)) {
            throw new Exception(sprintf('The ViewHelper "%s" may only be used inside "%s".', self::class, PaginationViewHelper::class), 1677234056);
        }

        /** @var Pagination $pagination */
        $pagination = $templateVariableContainer->get(PaginationViewHelper::PAGINATION_VARIABLE_IDENTIFIER);
        $iterator = GeneralUtility::makeInstance(Iterator::class, count($pagination->getStageLengths()));

        if ($selected) {
            $stages = [$pagination->getStages()->getSelected()];
        } elseif ($active) {
            $stages = $pagination->getStages()->getActive();
        } elseif ($inactive) {
            $stages = $pagination->getStages()->getInactive();
        } else {
            $stages = $pagination->getStages()->toArray();
        }

        $output = '';
        foreach ($stages as $stage) {
            $templateVariableContainer->add($iteration, $iterator);
            $templateVariableContainer->add($as, $stage);
            $templateVariableContainer->add(self::STAGE_VARIABLE_IDENTIFIER, $stage);

            $output .= $renderChildrenClosure();

            $templateVariableContainer->remove($iteration);
            $templateVariableContainer->remove($as);
            $templateVariableContainer->remove(self::STAGE_VARIABLE_IDENTIFIER);

            $iterator->count();
        }

        return $output;
    }
}
