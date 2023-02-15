<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Zeroseven\Z7Blog\Service\RequestService;

abstract class AbstractController extends ActionController
{
    /** @var array */
    protected $contentData;

    /** @var array */
    protected $requestArguments;

    public function initializeAction()
    {
        parent::initializeAction();

        /** @extensionScannerIgnoreLine */
        $this->contentData = $this->configurationManager->getContentObject()->data;
        $this->requestArguments = RequestService::getArguments();
    }
}
