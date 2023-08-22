<?php

defined('TYPO3') || die('📄');

\Zeroseven\Pagebased\Hooks\DataHandler\ResortPageTree::register();
\Zeroseven\Pagebased\Hooks\DataHandler\IdentifierDetection::register();
\Zeroseven\Pagebased\Hooks\IconFactory\OverrideIconOverlay::register();
\Zeroseven\Pagebased\Middleware\RssFeed::registerCache();
