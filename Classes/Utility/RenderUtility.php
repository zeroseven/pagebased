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
use Zeroseven\Rampage\Domain\Model\PageObjectInterface;
use Zeroseven\Rampage\Event\AssignTemplateVariablesEvent;
use Zeroseven\Rampage\Registration\Registration;

class RenderUtility
{
    /**
     * Back reference to the parent content object
     * This has to be public as it is set directly from TYPO3
     */
    public ?ContentObjectRenderer $cObj = null;

    protected function initializeView(Registration $registration, array $settings): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);

        $view->getRequest()->setControllerExtensionName(GeneralUtility::underscoredToLowerCamelCase($registration->getExtensionName()));
        $view->getRequest()->setControllerName($registration->getObject()->getName());
        $view->setTemplateRootPaths($settings['view']['templateRootPaths'] ?? []);
        $view->setPartialRootPaths($settings['view']['partialRootPaths'] ?? []);
        $view->setLayoutRootPaths($settings['view']['layoutRootPaths'] ?? []);
        $view->setFormat('html');

        return $view;
    }

    public function render(string $templateNameAndFilePath, string $registrationIdentifier, array $settings = null, PageObjectInterface $object = null): string
    {
        $pageUid = ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController
            ? (int)$GLOBALS['TSFE']->id
            : null;

        if ($pageUid && ($registration = ObjectUtility::isObject($pageUid)) && $registration->getIdentifier() === $registrationIdentifier) {
            $settings = array_merge($settings ?? [], SettingsUtility::getPluginConfiguration($registration));
            $view = $this->initializeView($registration, $settings);


            try {
                $object || $object = $registration->getObject()->getRepositoryClass()->findByUid($pageUid);
            } catch (AspectNotFoundException $e) {
            }

            if ($object) {
                $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndFilePath));
                $view->assignMultiple(GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new AssignTemplateVariablesEvent([
                    'object' => $object,
                    'settings' => $settings,
                    'data' => $this->cObj->data ?? [],
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
            throw new ContentRenderingException('Configuration "registration" (the identifier of a registration) is not set or empty.', 1683709644);
        }

        return $content . $this->render($file, $registrationIdentifier, $settings);
    }
}
