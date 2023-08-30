<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception as PersistenceException;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
use Zeroseven\Pagebased\Domain\Model\ObjectInterface;
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
    public ?ContentObjectRenderer $cObj = null;

    protected function initializeView(Registration $registration, array $pluginConfiguration): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);

        $view->setTemplateRootPaths($pluginConfiguration['view']['templateRootPaths'] ?? []);
        $view->setPartialRootPaths($pluginConfiguration['view']['partialRootPaths'] ?? []);
        $view->setLayoutRootPaths($pluginConfiguration['view']['layoutRootPaths'] ?? []);
        $view->setFormat('html');

        return $view;
    }

    /** @throws TypeException */
    public function render(string $templateNameAndFilePath, mixed $registrationIdentifiers, int $pageUid = null, array $settings = null, DomainObjectInterface $object = null): string
    {
        $pageUid || $pageUid = RootLineUtility::getCurrentPage();

        if ($pageUid && ($registration = ObjectUtility::isObject($pageUid)) && in_array($registration->getIdentifier(), CastUtility::array($registrationIdentifiers), true)) {
            $pluginConfiguration = SettingsUtility::getPluginConfiguration($registration);
            $view = $this->initializeView($registration, $pluginConfiguration);

            try {
                $object || $object = $registration->getObject()->getRepositoryClass()->findByUid($pageUid);
            } catch (AspectNotFoundException|TypeException|InvalidQueryException|PersistenceException|RegistrationException $e) {
                return '';
            }

            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndFilePath));
            $view->assignMultiple(GeneralUtility::makeInstance(EventDispatcher::class)?->dispatch(new AssignTemplateVariablesEvent([
                'object' => $object,
                'demand' => $registration->getObject()->getDemandClass(),
                'settings' => array_merge($pluginConfiguration['settings'] ?? [], $settings ?? []),
                'data' => $this->cObj->data ?? [],
                'registration' => $registration,
                strtolower($registration->getObject()->getName()) => $object // alias variable
            ], $registration, 'info'))->getVariables());

            return $view->render();
        }

        return '';
    }

    /** @throws ContentRenderingException | TypeException */
    public function renderUserFunc(string $content, array $conf): string
    {
        $file = $conf['file'] ?? null;
        $registrationIdentifiers = $conf['registration'] ?? ($conf['registration.'] ?? null);
        $settings = ($configSettings = $conf['settings.'] ?? null) ? GeneralUtility::makeInstance(TypoScriptService::class)->convertTypoScriptArrayToPlainArray($configSettings) : null;

        if ($file === null) {
            throw new ContentRenderingException('Configuration "file" is not set or empty.', 1683709643);
        }

        if ($registrationIdentifiers === null) {
            $validIdentifier = array_map(static fn($registration) => '"' . $registration->getIdentifier() . '"', RegistrationService::getRegistrations());

            throw new ContentRenderingException('Configuration "registration" (the identifier of a registration) is not set or empty.' . (count($validIdentifier) ? ' Valid identifiers are ' . implode(',', $validIdentifier) . '.' : ''), 1685960418);
        }

        return $content . $this->render($file, $registrationIdentifiers, $settings);
    }
}
