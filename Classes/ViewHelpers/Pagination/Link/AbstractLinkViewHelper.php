<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\ViewHelpers\Pagination\Link;

use ReflectionClass;
use TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper;
use TYPO3\CMS\Install\ViewHelpers\Exception;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractDemand;
use Zeroseven\Rampage\Pagination\Pagination;
use Zeroseven\Rampage\ViewHelpers\PaginationViewHelper;

abstract class AbstractLinkViewHelper extends ActionViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        // Register demand argument
        $this->registerArgument('demand', 'object', 'The demand object', false);
        $this->registerArgument('required', 'bool', 'Hide link-tag if target page is not available.', false, true);

    }

    abstract protected function getTargetStage(Pagination $pagination): ?int;

    /** @throws Exception */
    public function render(): string
    {
        if (!$this->templateVariableContainer->exists(PaginationViewHelper::PAGINATION_VARIABLE_IDENTIFIER)) {
            throw new Exception(sprintf('The ViewHelper "%s" may only be used inside "%s".', self::class, PaginationViewHelper::class), 1677243233);
        }

        if (($targetStage = $this->getTargetStage($this->templateVariableContainer->get(PaginationViewHelper::PAGINATION_VARIABLE_IDENTIFIER))) !== null) {
            if ($demand = $this->arguments['demand'] ?? null) {
                $overrides = $demand->getDiff($this->templateVariableContainer->get('settings'), [AbstractDemand::PARAMETER_UID_LIST]);

                foreach ($overrides as $key => $value) {
                    $this->arguments['arguments'][$key] = $value;
                }
            }

            $this->arguments['arguments'][PaginationViewHelper::REQUEST_PARAMETER] = $targetStage;
        } else {
            if ($this->arguments['required'] ?? false) {
                return '<!-- ' . (new ReflectionClass($this))->getShortName() . ': No target stage -->';
            }

            return $this->renderChildren();
        }

        return parent::render();
    }
}
