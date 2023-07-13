<?php

defined('TYPO3') || die('📄');

call_user_func(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('pagebased', 'Configuration/TypoScript/', 'Pagebased');
});
