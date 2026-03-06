<?php

/**
 * CaptainHook Bootstrap File
 *
 * This file provides the autoloader for CaptainHook.
 */

$autoloadFiles = [
    __DIR__ . '/.build/vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../../autoload.php',
];

foreach ($autoloadFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        return;
    }
}

throw new RuntimeException('Composer autoload.php could not be found. Run "composer install" first.');
