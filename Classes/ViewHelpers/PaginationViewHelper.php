<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Install\ViewHelpers\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Zeroseven\Pagebased\Pagination\Pagination;

final class PaginationViewHelper extends AbstractViewHelper
{
    public const PAGINATION_VARIABLE_IDENTIFIER = '📄-fe7cd4d1bf3fea9a0d921e224b3fa24c';

    // md5('pagination');
    public const REQUEST_ARGUMENT = '_stage';

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('items', 'array', 'The array or \SplObjectStorage to iterated over', true);
        $this->registerArgument('itemsPerStage', 'int|string', 'Number of items per stage. Add "4, 8" to display 4 elements for the first stage and 8 for the second and all subsequent ones.');
        $this->registerArgument('maxStages', 'int', 'Maximum number of stages');
        $this->registerArgument('as', 'string', 'The name of the iteration variable', false, self::PAGINATION_VARIABLE_IDENTIFIER);
    }

    private function getSelectedStage(): int
    {
        if (($request = $this->renderingContext->getAttribute(ServerRequestInterface::class)) instanceof RequestInterface && $request->hasArgument(self::REQUEST_ARGUMENT)) {
            return (int)$request->getArgument(self::REQUEST_ARGUMENT);
        }

        return 0;
    }

    /** @throws Exception */
    public function render(): string
    {
        $selectedStage = $this->getSelectedStage();
        $as = (empty($as = $this->arguments['as'] ?? null) || $as === self::PAGINATION_VARIABLE_IDENTIFIER) ? null : $as;

        if (empty($items = $this->arguments['items'] ?? null) || (is_object($items) && !$items instanceof \Traversable)) {
            throw new Exception('ForViewHelper only supports arrays and objects implementing \Traversable interface', 1677229957);
        }

        $variableProvider = $this->renderingContext->getVariableProvider();
        $pagination = GeneralUtility::makeInstance(Pagination::class, $items, $selectedStage, $this->arguments['itemsPerStage'] ?? null, $this->arguments['maxStages'] ?? null);

        $as && $variableProvider->add($as, $pagination);
        $variableProvider->add(self::PAGINATION_VARIABLE_IDENTIFIER, $pagination);

        $output = $this->renderChildren();

        $as && $variableProvider->remove($as);
        $variableProvider->remove(self::PAGINATION_VARIABLE_IDENTIFIER);

        return $output;
    }
}
