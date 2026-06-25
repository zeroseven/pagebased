<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Controller;

use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

abstract class AbstractController extends ActionController
{
    protected ?array $contentData = null;

    protected function initializeAction(): void
    {
        parent::initializeAction();

        $contentObject = $this->request->getAttribute('currentContentObject');
        $this->contentData = $contentObject?->data;
    }

    protected function resolveView(): ViewInterface
    {
        $view = parent::resolveView();
        $view->assign('data', $this->contentData);

        return $view;
    }
}
