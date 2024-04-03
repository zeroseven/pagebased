<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\EventListener;

use TYPO3\CMS\Frontend\Event\FilterMenuItemsEvent;

class HideObjectsInMenu
{
    public function __invoke(FilterMenuItemsEvent $event)
    {
        // Remove objects from menu
    }
}
