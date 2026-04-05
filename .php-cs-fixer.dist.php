<?php

declare(strict_types=1);

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->setParallelConfig(\PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect());
$config->getFinder()
    ->exclude(['.build'])
    ->in([__DIR__ . '/Classes', __DIR__ . '/Configuration', __DIR__ . '/Tests']);
$config->setCacheFile('.build/.php-cs-fixer.cache');

return $config;
