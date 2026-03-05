# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Pagebased is a TYPO3 extension that allows managing content objects (news, events, blog posts, jobs, etc.) as normal TYPO3 pages instead of database records. This provides full TYPO3 functionality like metadata, translation handling, caching, sitemaps, and URLs without additional configuration.

## Development Commands

### Frontend Assets
- `npm install` - Install dependencies (automatically copies tagify.css to Resources/Public/Css/Backend/TagsElement.css)
- `npm run dev` - Start Vite development server
- `npm run build` - Build frontend assets for production
- `npm run preview` - Preview production build

### TYPO3 Commands
- `pagebased:detection <uid> [depth]` - Update registration information for category and object pages
  - Example: `pagebased:detection 7` - Starting from page uid 7
  - Example: `pagebased:detection 7 2` - Starting from page uid 7 with depth of 2 levels
  - Useful when changing registration identifiers or adding pages via API

## Architecture

### Core Concept: Registration System

The extension uses a centralized registration system where page objects (jobs, news, events, etc.) are configured once in `ext_localconf.php` and all functionality is provided by the pagebased extension itself. This eliminates redundant code across multiple object implementations.

**Registration flow:**
1. Create `ObjectRegistration`, `CategoryRegistration`, `ListPluginRegistration`, and `FilterPluginRegistration` instances
2. Combine them into a `Registration` instance with a unique identifier
3. Call `->store()` to register with the system
4. System stores registration in `$GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['registrations']`

**Key classes:**
- `Registration` (Classes/Registration/Registration.php) - Main registration container
- `RegistrationService` (Classes/Registration/RegistrationService.php) - Global registry with lookups by class, controller, repository, etc.
- `ObjectRegistration` - Configures object properties (date, tags, topics, contacts, relations)
- `CategoryRegistration` - Configures category pages with document types
- `ListPluginRegistration` & `FilterPluginRegistration` - Configure plugins

### Domain Model Hierarchy

**Pages as Objects:**
- `AbstractPage` extends TYPO3's page table, all page objects stored in `pages` table
- `AbstractObject` extends `AbstractPage` - Base for all object types (news, jobs, events)
- `AbstractCategory` extends `AbstractPage` - Base for all category pages
- Objects are identified by their parent category's document type

**Key relationships:**
- Objects belong to Categories (parent page relationship)
- Objects can have Topics (many-to-many via ObjectStorage)
- Objects can have a Contact person (many-to-one)
- Objects can have Relations to other objects (bidirectional: relationsTo, relationsFrom)
- Objects can be top-flagged, dated, and tagged

### Controller & Repository Pattern

**Controllers:**
- `AbstractObjectController` - Base controller for all object types
- Implements `listAction()` and `filterAction()`
- Uses Demand pattern for filtering/querying
- Automatically resolves registration via `RegistrationService::getRegistrationByController()`

**Repositories:**
- `AbstractObjectRepository` - Base repository for object queries
- `AbstractCategoryRepository` - Base repository for category queries
- All extend TYPO3 Extbase repositories
- Use Demand objects to build dynamic queries

**Demand pattern:**
- `AbstractObjectDemand` - Encapsulates query parameters (filters, pagination, sorting)
- Each registration can specify custom demand class
- Demand properties automatically mapped from request arguments

### Event System

**Registration Events:**
- `BeforeStoreRegistrationEvent` - Modify registration before storing (for overriding defaults)
- `AfterStoreRegistrationEvent` - React after registration stored (TCA, TypoScript, plugins added here)
- `AddFlexFormEvent` - Extend plugin FlexForm configuration

**Other Events:**
- `AssignTemplateVariablesEvent` - Modify template variables before rendering
- Various RSS feed events in `Classes/Event/Rss/`

### Middleware

Three request middlewares (Configuration/RequestMiddlewares.php):
- `CategoryRedirect` - Handles category page redirects
- `RssFeed` - Generates RSS feeds at `/-/rss.xml` URLs for list plugins
- `StructuredData` - Adds structured data for SEO

### Creating New Extensions with Pagebased

Use cookiecutter to generate from template:
```bash
cookiecutter pagebased/Resources/Private/ExtensionDummy
```

This creates a new extension with:
- Model extending `AbstractObject` and `AbstractCategory`
- Repository extending `AbstractObjectRepository` and `AbstractCategoryRepository`
- Controller extending `AbstractObjectController`
- Complete registration in `ext_localconf.php`

### Key File Locations

- `Classes/Registration/` - Registration system
- `Classes/Domain/Model/` - Domain models (AbstractObject, AbstractCategory, AbstractPage)
- `Classes/Domain/Repository/` - Repositories
- `Classes/Controller/` - Controllers
- `Classes/Middleware/` - Request middlewares
- `Classes/ViewHelpers/` - Fluid ViewHelpers (condition, filter, pagination)
- `Classes/DataProcessing/` - TypoScript data processors (ObjectProcessor)
- `Classes/Pagination/` - Custom pagination implementation
- `Configuration/TCA/` - TCA for Contact and Topic models
- `Configuration/Services.yaml` - Dependency injection and event listeners
- `Resources/Private/ExtensionDummy/` - Cookiecutter template for new extensions

## Important Notes

- All object data is stored in the `pages` table
- Objects are identified by their relationship to category pages with specific document types
- The extension uses TYPO3 12.4+ and PHP 8.0+
- Registration identifiers must be unique across the system
- Categories require a document type to be set
- Use `RegistrationService` static methods to look up registrations by class/controller/repository