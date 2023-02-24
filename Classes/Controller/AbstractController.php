<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

abstract class AbstractController extends ActionController
{
    protected ?array $contentData;
    protected ?array $requestArguments;

    public function initializeAction()
    {
        parent::initializeAction();

        /** @extensionScannerIgnoreLine */
        $this->contentData = $this->configurationManager->getContentObject()->data;
        $this->requestArguments = $this->request->getArguments();
    }

    protected function resolveView(): ViewInterface
    {
        // Get "original" view object
        $view = parent::resolveView();

        // Assign variables to all actions
        $view->assignMultiple([
            'requestArguments' => $this->requestArguments,
            'data' => $this->contentData
        ]);

        return $view;
    }
}
