<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3Fluid\Fluid\View\ViewInterface;

abstract class AbstractController extends ActionController
{
    protected ?array $contentData;

    public function initializeAction(): void
    {
        parent::initializeAction();

        /** @extensionScannerIgnoreLine */
        $this->contentData = $this->configurationManager->getContentObject()->data;
    }

    protected function resolveView(): ViewInterface
    {
        $view = parent::resolveView();
        $view->assign('data', $this->contentData);

        return $view;
    }
}
