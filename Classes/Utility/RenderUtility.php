<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Zeroseven\Rampage\Domain\Model\ObjectInterface;
use Zeroseven\Rampage\Event\AssignTemplateVariablesEvent;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

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

        $view->getRequest()->setControllerExtensionName(GeneralUtility::underscoredToLowerCamelCase($registration->getExtensionName()));
        $view->getRequest()->setControllerName($registration->getObject()->getName());
        $view->setTemplateRootPaths($pluginConfiguration['view']['templateRootPaths'] ?? []);
        $view->setPartialRootPaths($pluginConfiguration['view']['partialRootPaths'] ?? []);
        $view->setLayoutRootPaths($pluginConfiguration['view']['layoutRootPaths'] ?? []);
        $view->setFormat('html');

        return $view;
    }

    public function render(string $templateNameAndFilePath, string $registrationIdentifier, array $settings = null, ObjectInterface $object = null): string
    {
        $pageUid = ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController
            ? (int)$GLOBALS['TSFE']->id
            : null;

        if ($pageUid && ($registration = ObjectUtility::isObject($pageUid)) && $registration->getIdentifier() === $registrationIdentifier) {
            $pluginConfiguration = SettingsUtility::getPluginConfiguration($registration);
            $view = $this->initializeView($registration, $pluginConfiguration);

            try {
                $object || $object = $registration->getObject()->getRepositoryClass()->findByUid($pageUid);
            } catch (AspectNotFoundException $e) {
            }

            if ($object) {
                $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndFilePath));
                $view->assignMultiple(GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new AssignTemplateVariablesEvent([
                    'object' => $object,
                    'demand' => $registration->getObject()->getDemandClass(),
                    'settings' => array_merge($pluginConfiguration['settings'] ?? [], $settings ?? []),
                    'data' => $this->cObj->data ?? [],
                    'registration' => $registration,
                    strtolower($registration->getObject()->getName()) => $object // alias variable
                ], $registration, 'info'))->getVariables());

                return $view->render();
            }
        }

        return '';
    }

    /** @throws ContentRenderingException */
    public function renderUserFunc(string $content, array $conf): string
    {
        $file = $conf['file'] ?? null;
        $registrationIdentifier = $conf['registration'] ?? null;
        $settings = ($configSettings = $conf['settings.'] ?? null) ? GeneralUtility::makeInstance(TypoScriptService::class)->convertTypoScriptArrayToPlainArray($configSettings) : null;

        if ($file === null) {
            throw new ContentRenderingException('Configuration "file" is not set or empty.', 1683709643);
        }

        if ($registrationIdentifier === null) {
            $validIdentifier = array_map(static fn($registration) => '"' . $registration->getIdentifier() . '"', RegistrationService::getRegistrations());

            throw new ContentRenderingException('Configuration "registration" (the identifier of a registration) is not set or empty.' . (count($validIdentifier) ? ' Valid identifiers are ' . implode(',', $validIdentifier) . '.' : ''), 1685960418);
        }

        return $content . $this->render($file, $registrationIdentifier, $settings);
    }
}
