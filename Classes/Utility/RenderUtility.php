<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception as PersistenceException;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
use Zeroseven\Pagebased\Event\AssignTemplateVariablesEvent;
use Zeroseven\Pagebased\Exception\RegistrationException;
use Zeroseven\Pagebased\Exception\TypeException;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;

class RenderUtility
{
    /**
     * Back reference to the parent content object
     * This has to be public as it is set directly from TYPO3
     */
    protected ?ContentObjectRenderer $cObj = null;

    public function __construct(private readonly EventDispatcher $eventDispatcher) {}

    protected function initializeView(Registration $registration, array $pluginConfiguration, string $templatePathAndFilename, ServerRequestInterface $serverRequest = null): ViewInterface
    {
        $serverRequest = RequestUtility::getExtbaseRequest($registration, $serverRequest) ?? $serverRequest ?? RequestUtility::getServerRequest();

        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: $pluginConfiguration['view']['templateRootPaths'] ?? [],
            partialRootPaths: $pluginConfiguration['view']['partialRootPaths'] ?? [],
            layoutRootPaths: $pluginConfiguration['view']['layoutRootPaths'] ?? [],
            templatePathAndFilename: GeneralUtility::getFileAbsFileName($templatePathAndFilename),
            request: $serverRequest,
            format: 'html',
        );

        return GeneralUtility::makeInstance(ViewFactoryInterface::class)->create($viewFactoryData);
    }

    /** @throws TypeException */
    public function render(string $templateNameAndFilePath, mixed $registrationIdentifiers, array $settings = null, int $pageUid = null, ServerRequestInterface $serverRequest = null, DomainObjectInterface $domainObject = null): string
    {
        $pageUid || $pageUid = RootLineUtility::getCurrentPage();

        if ($pageUid && ($registration = ObjectUtility::isObject($pageUid)) && in_array($registration->getIdentifier(), CastUtility::array($registrationIdentifiers), true)) {
            $pluginConfiguration = SettingsUtility::getPluginConfiguration($registration);
            $view = $this->initializeView($registration, $pluginConfiguration, $templateNameAndFilePath, $serverRequest);

            try {
                $domainObject || $domainObject = $registration->getObject()->getRepositoryClass()->findByUid($pageUid);
            } catch (AspectNotFoundException|TypeException|InvalidQueryException|PersistenceException|RegistrationException) {
                return '';
            }

            $view->assignMultiple($this->eventDispatcher?->dispatch(new AssignTemplateVariablesEvent([
                'object' => $domainObject,
                'demand' => $registration->getObject()->getDemandClass(),
                'settings' => array_merge($pluginConfiguration['settings'] ?? [], $settings ?? []),
                'data' => $this->cObj->data ?? [],
                'registration' => $registration,
                strtolower($registration->getObject()->getName()) => $domainObject, // alias variable
            ], $registration, 'info'))->getVariables());

            return $view->render();
        }

        return '';
    }

    /** @throws ContentRenderingException|TypeException */
    public function renderUserFunc(string $content, array $conf, ServerRequestInterface $serverRequest): string
    {
        $file = $conf['file'] ?? null;
        $registrationIdentifiers = $conf['registration'] ?? ($conf['registration.'] ?? null);
        $settings = ($configSettings = $conf['settings.'] ?? null) ? GeneralUtility::makeInstance(TypoScriptService::class)->convertTypoScriptArrayToPlainArray($configSettings) : null;

        if ($file === null) {
            throw new ContentRenderingException('Configuration "file" is not set or empty.', 1683709643);
        }

        if ($registrationIdentifiers === null) {
            $validIdentifier = array_map(static fn(Registration $registration): string => '"' . $registration->getIdentifier() . '"', RegistrationService::getRegistrations());

            throw new ContentRenderingException('Configuration "registration" (the identifier of a registration) is not set or empty.' . (count($validIdentifier) ? ' Valid identifiers are ' . implode(',', $validIdentifier) . '.' : ''), 1685960418);
        }

        return $content . $this->render($file, $registrationIdentifiers, $settings, null, $serverRequest);
    }

    public function setContentObjectRenderer(ContentObjectRenderer $contentObjectRenderer): void
    {
        $this->cObj = $contentObjectRenderer;
    }
}
