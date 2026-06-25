# Upgrade to TYPO3 v14

Version `3.0.0` drops TYPO3 v12 and supports **TYPO3 13.4 + 14** on PHP 8.2–8.4.

## What was changed

Configuration

- `composer.json`: `typo3/cms-core` → `^13.4 || ^14.0`, `typo3/cms-install` → `^13.4 || ^14.0`,
  `typo3/testing-framework` → `^9.0` (supports v13 + v14), `ssch/typo3-rector` → `^3.6`.
- `ext_emconf.php`: version `3.0.0`, constraint `13.4.0-14.4.99`.
- `rector.php`: `Typo3LevelSetList::UP_TO_TYPO3_14`, PHP set `php82`.

Code (manual migrations rector does not cover)

- New `Classes/Utility/FrontendRequestUtility.php` — facade over the request attributes
  that replace the removed `TypoScriptFrontendController` / `$GLOBALS['TSFE']`.
- `Middleware/StructuredData`, `Middleware/CategoryRedirect`, `Utility/RootLineUtility`,
  `Controller/AbstractObjectController`: page id / page record / root line now read via
  `frontend.page.information`; cache disabling via `frontend.cache.instruction` (v14) with
  a v13 `set_no_cache()` fallback.
- `Event/StructuredDataEvent`: image dimensions read from the processed file instead of
  the removed `TSFE->lastImageInfo`.
- `Utility/RenderUtility`: `StandaloneView` (removed in v14) → `ViewFactoryInterface`.
- `ViewHelpers/AbstractLinkViewHelper`, `ViewHelpers/PaginationViewHelper`:
  `RenderingContext::getRequest()` (removed in v14) → `getAttribute(ServerRequestInterface::class)`.
- `Controller/Abstract*Controller`: `resolveView()` return type → `TYPO3\CMS\Core\View\ViewInterface`
  (covers the v13 union and the v14 type); `filterAction` uses `$view->assign()` instead of the
  removed `getRenderingContext()->getVariableProvider()`.

- ViewHelpers migrated from the static `renderStatic()` / `CompileWithRenderStatic` pattern
  to the instance method `render()` (`PaginationViewHelper`, `Pagination/EachItemViewHelper`,
  `Pagination/EachStageViewHelper`). TYPO3 v14 ships **Fluid v5**, which removed the trait
  entirely — `renderStatic()` is not a deprecation here but a hard removal.

## Verify on your machine (DDEV)

These steps could not be run in the editing environment (no PHP runtime). Run them yourself:

```bash
# 1. Resolve dependencies for the new version range
ddev composer update

# 2. Apply automated TYPO3 14 / PHP refactorings
ddev composer fix:rector        # or: lint:rector for a dry-run first

# 3. Re-apply code style
ddev composer fix

# 4. Static analysis — the baseline was generated against v13 and must be regenerated
ddev exec .build/bin/phpstan analyse -c phpstan.neon --generate-baseline phpstan-baseline.neon
ddev composer sca:php

# 5. Tests
ddev composer test
```

Then smoke-test in a real v14 instance: an object detail page (structured data /
JSON-LD in the footer), a category redirect, a list/filter plugin with pagination,
and the `pagebased:detection` CLI command.
