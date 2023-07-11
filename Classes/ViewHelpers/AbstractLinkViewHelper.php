<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\ViewHelpers;

use RuntimeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use Zeroseven\Rampage\Exception\TypeException;
use Zeroseven\Rampage\Utility\CastUtility;

abstract class AbstractLinkViewHelper extends AbstractTagBasedViewHelper
{
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerUniversalTagAttributes();

        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('controller', 'string', 'Target controller. If NULL current controllerName is used');
        $this->registerArgument('extensionName', 'string', 'Target Extension Name (without `tx_` prefix and no underscores). If NULL the current extension name is used');
        $this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
        $this->registerArgument('pageUid', 'int', 'Target page. See TypoLink destination');
        $this->registerArgument('pageType', 'int', 'Type of the target page. See typolink.parameter');
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI');
        $this->registerArgument('additionalParams', 'array', 'Additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)');
        $this->registerArgument('absolute', 'bool', 'If set, the URI of the rendered link is absolute');
        $this->registerArgument('addQueryString', 'string', 'If set, the current query parameters will be kept in the URL. If set to "untrusted", then ALL query parameters will be added. Be aware, that this might lead to problems when the generated link is cached.', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'Arguments to be removed from the URI. Only active if $addQueryString = TRUE');
        $this->registerArgument('arguments', 'array', 'Arguments for the controller action, associative array');
    }

    protected function getRequest(): RequestInterface
    {
        return ($renderingContext = $this->renderingContext)
        && ($request = $renderingContext->getRequest())
        && ($request instanceof RequestInterface)
            ? $request
            : (throw new RuntimeException('ViewHelper "' . self::class . '" can be used only in extbase context and needs a request implementing extbase RequestInterface.', 1688559410));
    }

    /** @throws TypeException */
    public function render(): string
    {
        // Get variables
        $request = $this->getRequest();
        $action = $this->arguments['action'] ?? null;
        $controller = $this->arguments['controller'] ?? null;
        $extensionName = $this->arguments['extensionName'] ?? null;
        $pluginName = $this->arguments['pluginName'] ?? null;
        $pageUid = CastUtility::int($this->arguments['pageUid'] ?? 0);
        $pageType = CastUtility::int($this->arguments['pageType'] ?? 0);
        $section = CastUtility::string($this->arguments['section'] ?? '');
        $additionalParams = CastUtility::array($this->arguments['additionalParams'] ?? []);
        $absolute = CastUtility::bool($this->arguments['absolute'] ?? false);
        $addQueryString = CastUtility::bool($this->arguments['addQueryString'] ?? false);
        $argumentsToBeExcludedFromQueryString = CastUtility::array($this->arguments['argumentsToBeExcludedFromQueryString']);
        $arguments = CastUtility::array($this->arguments['arguments'] ?? []);

        // Create instance of the uriBuilder
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->reset()->setRequest($request)
            ->setCreateAbsoluteUri($absolute)
            ->setAddQueryString($addQueryString);

        // Apply variables
        empty($pageUid) || $uriBuilder->setTargetPageUid($pageUid);
        empty($pageType) || $uriBuilder->setTargetPageType($pageType);
        empty($section) || $uriBuilder->setSection($section);
        empty($additionalParams) || $uriBuilder->setArguments($additionalParams);
        empty($argumentsToBeExcludedFromQueryString) || $uriBuilder->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString);

        // Render link
        if ($uri = $uriBuilder->uriFor($action, $arguments, $controller, $extensionName, $pluginName)) {
            $this->tag->addAttribute('href', $uri);
            $this->tag->setContent($this->renderChildren());
            $this->tag->forceClosingTag(true);
            return $this->tag->render();
        }

        // Return default content
        return $this->renderChildren();
    }
}
