<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ViewHelpers\Pagination\Link;

use JsonException;
use ReflectionClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Install\ViewHelpers\Exception;
use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;
use Zeroseven\Pagebased\Exception\TypeException;
use Zeroseven\Pagebased\Pagination\Pagination;
use Zeroseven\Pagebased\Utility\CastUtility;
use Zeroseven\Pagebased\ViewHelpers\AbstractLinkViewHelper;
use Zeroseven\Pagebased\ViewHelpers\PaginationViewHelper;

abstract class AbstractPaginationLinkViewHelper extends AbstractLinkViewHelper
{
    public const AJAX_CONTENT_PARAMETER = '_pagebased_content';

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        // Register demand argument
        $this->registerArgument('required', 'bool', 'Hide link-tag if target page is not available.', false, true);
        $this->registerArgument('ajaxReplaceSelectors', 'string|array', 'One or more selectors that need to be replaced in an Ajax request.');
        $this->registerArgument('ajaxAppendSelectors', 'string|array', 'One or more selectors that content from the Ajax request will be appended.');
    }

    abstract protected function getTargetStage(Pagination $pagination): ?int;

    /** @throws Exception | TypeException| JsonException */
    public function render(): string
    {
        if (!$this->templateVariableContainer->exists(PaginationViewHelper::PAGINATION_VARIABLE_IDENTIFIER)) {
            throw new Exception(sprintf('The ViewHelper "%s" may only be used inside "%s".', self::class, PaginationViewHelper::class), 1677243233);
        }

        $demand = $this->arguments['demand'] ?? null;

        if (($targetStage = $this->getTargetStage($this->templateVariableContainer->get(PaginationViewHelper::PAGINATION_VARIABLE_IDENTIFIER))) !== null) {
            if ($demand) {
                $overrides = $demand->getParameterDiff($this->templateVariableContainer->get('settings'), [DemandInterface::PROPERTY_UID_LIST]);

                foreach ($overrides as $key => $value) {
                    $this->arguments['arguments'][$key] = $value;
                }
            }

            $this->arguments['arguments'][PaginationViewHelper::REQUEST_ARGUMENT] = $targetStage;
        } else {
            if ($this->arguments['required'] ?? false) {
                return '<!-- ' . (new ReflectionClass($this))->getShortName() . ': No target stage -->';
            }

            return $this->renderChildren();
        }

        // Add a "data-href" link attribute
        $replaceSelectors = CastUtility::array($this->arguments['ajaxReplaceSelectors'] ?? []);
        $appendSelectors = CastUtility::array($this->arguments['ajaxAppendSelectors'] ?? []);

        if (count($replaceSelectors) + count($appendSelectors) > 0) {
            $variableProvider = $this->renderingContext->getVariableProvider();

            if ($demand && $demand->getContentId() && ($ajaxTypeNum = (int)($variableProvider->get('settings.list.ajaxTypeNum') ?? 0))) {
                $ajaxUrl = GeneralUtility::makeInstance(UriBuilder::class)->reset()->setRequest($this->getRequest())
                    ->setCreateAbsoluteUri(true)
                    ->setTargetPageType($ajaxTypeNum)
                    ->setArguments((array)($this->arguments['arguments'] ?? []))
                    ->setAddQueryString((bool)($this->arguments['addQueryString'] ?? false))
                    ->setArguments(array_merge((array)($this->arguments['additionalParams'] ?? []), [self::AJAX_CONTENT_PARAMETER => $demand->getContentId()]))
                    ->uriFor($this->arguments['action'] ?? '', array_merge(($this->arguments['arguments'] ?? []), [
                        '_ajax' => 1
                    ]), $this->arguments['controller'] ?? null, $this->arguments['extensionName'] ?? null, $this->arguments['pluginName'] ?? null);

                $ajaxUrl && $this->tag->addAttribute('onclick', sprintf('Zeroseven.Pagebased.Pagination.load(%s,%s,%s,event)', GeneralUtility::quoteJSvalue($ajaxUrl), json_encode($replaceSelectors, JSON_THROW_ON_ERROR), json_encode($appendSelectors, JSON_THROW_ON_ERROR)));
            } else {
                throw new Exception('Ajax-Loading failed: Either the content ID of the demand class or the key "list.ajaxTypeNum" is not configured in your plugin settings.', 1677489279);
            }
        }

        return parent::render();
    }
}
