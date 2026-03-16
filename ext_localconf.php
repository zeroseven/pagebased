<?php

defined('TYPO3') || die('📄');

\Zeroseven\Pagebased\Hooks\DataHandler\ResortPageTree::register();
\Zeroseven\Pagebased\Hooks\DataHandler\IdentifierDetection::register();
\Zeroseven\Pagebased\Hooks\IconFactory\OverrideIconOverlay::register();
\Zeroseven\Pagebased\Middleware\RssFeed::registerCache();

// Opt-in: scope tag queries to the blog category found in the current page's rootline.
// Enable per installation in AdditionalConfiguration.php or via the Install Tool Features panel.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['pagebased.nonglobalTags'] ??= false;

// Tag query result cache – stores distinct tag lists per registration/category/language.
// TYPO3 clears this automatically when pages with matching tags are modified.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pagebased_tags'] ??= [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
    'backend'  => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
    'options'  => [
        'defaultLifetime' => 86400, // 24 hours; TYPO3 page-save clears relevant entries
    ],
    'groups'   => ['pages'],
];
