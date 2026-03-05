<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/Tests',
    ])
    ->withSkip([
        __DIR__ . '/.build',
        __DIR__ . '/vendor',
        __DIR__ . '/ext_emconf.php',

        // Repositories need inject methods for proper Extbase initialization
        \Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/Classes/Domain/Repository/*',
        ],
        \Ssch\TYPO3Rector\CodeQuality\General\InjectMethodToConstructorInjectionRector::class => [
            __DIR__ . '/Classes/Domain/Repository/*',
        ],
    ])
    ->withPhpSets(php81: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
    )
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        Typo3LevelSetList::UP_TO_TYPO3_12,
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withParallel(maxNumberOfProcess: 4, jobSize: 10)
    ->withCache(cacheDirectory: '.build/rector-cache');
