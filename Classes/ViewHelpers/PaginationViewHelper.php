<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\ViewHelpers;

use Closure;
use Traversable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\ViewHelpers\Exception;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use Zeroseven\Rampage\Pagination\Pagination;

class PaginationViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public const PAGINATION_VARIABLE_IDENTIFIER = '🤬-fe7cd4d1bf3fea9a0d921e224b3fa24c'; // md5('pagination');

    protected $escapeOutput = false;

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('items', 'array', 'The array or \SplObjectStorage to iterated over', true);
        $this->registerArgument('itemsPerStage', 'int|string', 'Number of items per stage. Add "4, 8" to display 4 elements for the first stage and 8 for the second and all subsequent ones.');
        $this->registerArgument('maxStages', 'int', 'Maximum number of stages');
        $this->registerArgument('as', 'string', 'The name of the iteration variable', false, self::PAGINATION_VARIABLE_IDENTIFIER);
    }

    /** @throws Exception */
    public static function renderStatic(array $arguments, Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $selectedStage = 0;
        $as = (empty($as = $arguments['as'] ?? null) || $as === self::PAGINATION_VARIABLE_IDENTIFIER) ? null : $as;

        if (empty($items = $arguments['items'] ?? null) || (is_object($items) && !$items instanceof Traversable)) {
            throw new Exception('ForViewHelper only supports arrays and objects implementing \Traversable interface', 1677229957);
        }

        $templateVariableContainer = $renderingContext->getVariableProvider();
        $pagination = GeneralUtility::makeInstance(Pagination::class, $items, $selectedStage, $arguments['itemsPerStage'] ?? null, $arguments['maxStages'] ?? null);

        $as && $templateVariableContainer->add($as, $pagination);
        $templateVariableContainer->add(self::PAGINATION_VARIABLE_IDENTIFIER, $pagination);

        $output = $renderChildrenClosure();

        $as && $templateVariableContainer->remove($as);
        $templateVariableContainer->remove(self::PAGINATION_VARIABLE_IDENTIFIER);

        return $output;
    }
}
