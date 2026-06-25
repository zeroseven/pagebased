# Pagebased — Domain Context

## Glossary

### Object
A TYPO3 page that represents a domain entity (e.g. a blog post, a job, a news item). Identified by a `doktype` registered via `Registration` and marked with a `tx_pagebased_registration` field set by the detection mechanism. An Object always belongs to exactly one `Registration`.

### Category
A TYPO3 page that acts as a container for `Object` pages of a given `Registration`. Identified by its `doktype` being registered as the category type in a `Registration`.

### System Page
A TYPO3 page with a built-in structural `doktype` (SysFolder, Spacer, MountPoint, BE User Section). Neither an Object nor a Category.

### Registration
The descriptor for one page-based entity type. Holds the configuration for one Object type and its associated Category type, plugins, TypoScript, and icons. Stored and retrieved via `RegistrationService`.

### PageClassifier
The service that answers "what kind of page is this?" — returns the `Registration` if the page is an Object or Category, `bool` for system pages, and `null` when the page is none of the above. The concrete implementation (`PageClassifier`) is a `SingletonInterface`; extensions may swap it by aliasing `PageClassifierInterface` in their `Services.yaml`.

### ObjectUtility
A static facade over `PageClassifier`. Exists for call-site convenience and backwards compatibility. Does not hold logic; delegates all calls to a `PageClassifierInterface` instance obtained via `GeneralUtility::makeInstance`.

### Detection
The process that writes the `tx_pagebased_registration` identifier onto a page record when the page is saved in the backend. Triggered via `DataHandler` hooks (`IdentifierDetection`) and the `pagebased:detection` CLI command.

### Tag
A free-text label attached to Object pages. Tags are scoped per `Registration` and optionally per Category (via the `pagebased.nonglobalTags` feature flag).

## Compatibility Matrix

| TYPO3  | PHP             | Status        |
|--------|-----------------|---------------|
| 12.4.x | 8.2 / 8.3       | dropped in 3.0 |
| 13.4.x | 8.2 / 8.3 / 8.4 | supported     |
| 14.x   | 8.2 / 8.3 / 8.4 | supported     |

TypoScript is registered via `addStaticFile()` (all versions) and additionally via Site Sets (v13+, `Configuration/Sets/Pagebased/`).

### TYPO3 v14 notes

`TypoScriptFrontendController` / `$GLOBALS['TSFE']` were removed in v14. Page id,
page record and root line are now read through `FrontendRequestUtility`, which wraps
the `frontend.page.information` request attribute (v13+). Cache disabling uses the
`frontend.cache.instruction` attribute on v14 and falls back to
`TypoScriptFrontendController::set_no_cache()` on v13. Fluid's removed
`RenderingContext::getRequest()` is replaced by `getAttribute(ServerRequestInterface::class)`,
and `StandaloneView` (removed in v14) is replaced by `ViewFactoryInterface` in `RenderUtility`.

TYPO3 v14 ships Fluid v5, which **removed** the `CompileWithRenderStatic` trait and the
static `renderStatic()` rendering path. All ViewHelpers (`PaginationViewHelper`,
`Pagination/EachItemViewHelper`, `Pagination/EachStageViewHelper`) were therefore migrated
to the instance method `render()` using `$this->arguments`, `$this->renderingContext` and
`$this->renderChildren()`.
