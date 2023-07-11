<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface;

interface ObjectControllerInterface extends ControllerInterface
{
    public function listAction(): ResponseInterface;
}
