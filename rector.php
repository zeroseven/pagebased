<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
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

        // RssFeed has a constructor argument wired by name in Services.yaml
        // ($frontend). Renaming it to match the type would break that binding.
        \Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector::class => [
            __DIR__ . '/Classes/Middleware/RssFeed.php',
        ],
    ])
    ->withPhpSets(php82: true)
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
        Typo3LevelSetList::UP_TO_TYPO3_14,
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withParallel(maxNumberOfProcess: 4, jobSize: 10)
    ->withCache(cacheDirectory: '.build/rector-cache');
