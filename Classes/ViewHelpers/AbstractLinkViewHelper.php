<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use RuntimeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;
use Zeroseven\Pagebased\Exception\TypeException;
use Zeroseven\Pagebased\Exception\ValueException;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;
use Zeroseven\Pagebased\Utility\CastUtility;
use Zeroseven\Pagebased\Utility\ObjectUtility;

abstract class AbstractLinkViewHelper extends AbstractTagBasedViewHelper
{
    protected $tagName = 'a';

    private ?RequestInterface $request = null;
    protected ?DemandInterface $demand = null;
    protected ?Registration $registration = null;

    public function initialize()
    {
        parent::initialize();

        $this->tag->forceClosingTag(true);
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('demand', 'object', sprintf('The demand object (instance of %s)', DemandInterface::class));
        $this->registerArgument('registration', 'string', 'The registration identifier');
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('controller', 'string', 'Target controller. If NULL current controllerName is used');
        $this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
        $this->registerArgument('pageUid', 'int', 'Target page. See TypoLink destination');
        $this->registerArgument('pageType', 'int', 'Type of the target page. See typolink.parameter');
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI');
        $this->registerArgument('additionalParams', 'array', 'Additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)');
        $this->registerArgument('absolute', 'bool', 'If set, the URI of the rendered link is absolute');
        $this->registerArgument('addQueryString', 'string', 'If set, the current query parameters will be kept in the URL. If set to "untrusted", then ALL query parameters will be added. Be aware, that this might lead to problems when the generated link is cached.', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'Arguments to be removed from the URI. Only active if $addQueryString = TRUE');
        $this->registerArgument('arguments', 'array', 'Arguments for the controller action, associative array');

        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
    }

    protected function getRequest(): RequestInterface
    {
        if ($this->request === null) {
            if (
                ($renderingContext = $this->renderingContext) instanceof RenderingContextInterface
                && ($request = $renderingContext->getRequest()) instanceof RequestInterface
            ) {
                return $this->request = $request;
            }

            if (
                ($serverRequest = $GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
                && $pluginName = $this->registration->getListPlugin()?->getType() ?? $this->registration->getFilterPlugin()?->getType()
            ) {
                $bootstrapInitialization = GeneralUtility::makeInstance(Bootstrap::class)?->initialize([
                    'extensionName' => GeneralUtility::underscoredToUpperCamelCase($this->registration->getExtensionName()),
                    'pluginName' => ucfirst($pluginName),
                    'vendorName' => strtok($this->registration->getObject()->getClassName(), '\\'),
                ], $serverRequest);

                if (($request = GeneralUtility::makeInstance(RequestBuilder::class)?->build($bootstrapInitialization)) instanceof RequestInterface) {
                    return $this->request = $request;
                }
            }

            throw new RuntimeException('ViewHelper "' . self::class . '" can be used only in extbase context and needs a request implementing extbase RequestInterface.', 1688559410);
        }

        return $this->request;
    }

    /** @throws ValueException */
    public function validateArguments(): void
    {
        parent::validateArguments();

        $this->initializeRegistration();
    }

    /** @throws ValueException | Exception */
    protected function initializeRegistration(): void
    {
        // Try to get demand
        if (($demand = $this->arguments['demand'] ?? $this->templateVariableContainer->get('demand')) instanceof DemandInterface) {
            $this->demand = $demand->getCopy();
        }

        // Try to get registration
        if (($registrationIdentifier = $this->arguments['registration'] ?? null) && $registration = RegistrationService::getRegistrationByIdentifier($registrationIdentifier)) {
            $this->registration = $registration;
        } elseif (($registration = $this->templateVariableContainer->get('registration')) instanceof Registration) {
            $this->registration = $registration;
        } else {
            $this->registration = ObjectUtility::isObject() ?? ObjectUtility::isCategory();
        }

        // Try to get registration by the demand class
        if ($this->registration === null && $this->demand) {
            $this->registration = RegistrationService::getRegistrationByDemand($this->demand);
        }

        // Try to get demand from registration
        if ($this->registration && $this->demand === null) {
            $this->demand = $this->registration->getObject()->getDemandClass();
        }

        // Unfortunately didn't work :(
        if ($this->registration === null && $this->demand === null) {
            throw new Exception(sprintf('The registration object and demand object could not be determined. Add arguments "registration" or "demand" to the ViewHelper ("%s").', get_class($this)), 1690362083);
        }
    }

    /** @throws TypeException */
    protected function createUri(): string
    {
        // Get variables
        $action = $this->arguments['action'] ?? null;
        $controller = $this->arguments['controller'] ?? null;
        $pluginName = $this->arguments['pluginName'] ?? null;
        $pageUid = CastUtility::int($this->arguments['pageUid'] ?? 0);
        $pageType = CastUtility::int($this->arguments['pageType'] ?? 0);
        $section = CastUtility::string($this->arguments['section'] ?? '');
        $additionalParams = CastUtility::array($this->arguments['additionalParams'] ?? []);
        $absolute = CastUtility::bool($this->arguments['absolute'] ?? false);
        $addQueryString = CastUtility::bool($this->arguments['addQueryString'] ?? false);
        $argumentsToBeExcludedFromQueryString = CastUtility::array($this->arguments['argumentsToBeExcludedFromQueryString']);
        $arguments = CastUtility::array($this->arguments['arguments'] ?? []);

        // Set plugin name
        $pluginName ?? $pluginName = 'List';

        // Set controller name
        if (empty($controller) && $this->renderingContext->getControllerName() === 'Standard') {
            $controller = GeneralUtility::makeInstance(ReflectionClass::class, $this->registration->getObject()->getClassName())->getShortName();
        }

        // Create instance of the uriBuilder
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->reset()->setRequest($this->getRequest())
            ->setCreateAbsoluteUri($absolute)
            ->setAddQueryString($addQueryString);

        // Apply variables
        empty($pageUid) || $uriBuilder->setTargetPageUid($pageUid);
        empty($pageType) || $uriBuilder->setTargetPageType($pageType);
        empty($section) || $uriBuilder->setSection($section);
        empty($additionalParams) || $uriBuilder->setArguments($additionalParams);
        empty($argumentsToBeExcludedFromQueryString) || $uriBuilder->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString);

        // Set extension name
        $extensionName = GeneralUtility::underscoredToLowerCamelCase($this->registration->getExtensionName());

        return $uriBuilder->uriFor($action, $arguments, $controller, $extensionName, $pluginName);
    }

    /** @throws TypeException */
    public function render(): string
    {
        // Render link
        if ($uri = $this->createUri()) {
            $this->tag->addAttribute('href', $uri);
            $this->tag->setContent($this->renderChildren());

            return $this->tag->render();
        }

        // Return default content
        return $this->renderChildren();
    }
}
