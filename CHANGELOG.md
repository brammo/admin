# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **FormHelper** (`dateRange`): `value` as `[$from, $to]` or `['from' => …, 'to' => …]`; `valueFrom` / `valueTo` for separate defaults

### Changed
- Refactor pageHeader layout and update styles for improved responsiveness and padding adjustments in page-header and page-content sections

## [1.5.0] - 2026-06-02

### Added
- **FormHelper**: `dateRange` control type — Bootstrap input group with two date fields (`{name}_from` / `{name}_to` by default)
- **FormHelper**: `dateRange()` method for input-group markup only; `control(['type' => 'dateRange'])` adds label and form-group wrapper
- **FormHelper**: `suffixes` option for custom field suffixes (e.g. `['start', 'end']` → `{name}_start`, `{name}_end`)
- Tests and documentation for the date range control (`docs/HELPERS.md`)

## [1.4.0] - 2026-04-24

### Added
- **FileManagerService**: Filesystem logic extracted from the controller (path validation, uploads, image resize)
- **FormHelper**: `html` control type with TinyMCE WYSIWYG editor
- Plugin authentication defaults in `config/auth.php` (`AdminAuth` session key, `AdminCookieAuth` cookie)
- **AppView**: `Authentication.Identity` helper; `Brammo/Content` helpers (`Date`, `Image`, `Flag`)
- Tests for `htmlControl()`; `FileManagerService` unit tests
- `phpstan.neon` and `AGENTS.md` for static analysis and agent workflows
- Optional `ext-imagick` suggested in Composer for File Manager image resize

### Changed
- Require **PHP 8.2+** and **CakePHP 5.3+**; upgrade `friendsofcake/bootstrap-ui` to ^5.1
- Replace Summernote with **TinyMCE** for rich text (`Admin.Editor.apiKey` configuration)
- **FileManagerController** uses lazy-loaded `FileManagerService::fromConfigure()`
- Refactor **UserController** profile action
- README: requirements, File Manager `imagick` note, and `composer analyse` workflow

## [1.3.0] - 2026-04-03

### Added
- **FileManagerController**: Built-in file and image manager with upload, browse, and management capabilities
- **FormHelper**: Added `image` control type for image picker/uploader integration with File Manager
- File Manager documentation (`docs/FILEMANAGER.md`)
- Tests for `FormHelper::imageControl()` covering entity handling and UI rendering
- Flag icon styles
- Target option to ButtonHelper for link rendering
- PHPStan configuration for improved static analysis

### Changed
- Refactor FileManager and Form image element for improved security and usability
- Update composer dependencies for static analysis tooling
- Enhanced button, badge, and tab styles
- Updated form and table elements
- Added translations for various UI elements and improve existing entries

### Fixed
- Fix default language configuration in AppController

## [1.2.0] - 2026-02-21

### Added
- Add title configuration and element for admin panel layout

### Changed
- Rename Plugin to AdminPlugin as it's deprecated in CakePHP 5.3
- Update admin configuration to remove I18n settings and use defaultLocale
- Refactor CSS variables and styles for improved theme consistency
- Refactor page header to use a variable for page heading
- Refactor login layout
- Refactor breadcrumbs element to streamline icon handling
- Update view variable annotations to use the correct namespace
- Update composer dependencies and enhance AppView helper properties
- Update development dependencies and improve script commands in composer.json
- Refactor locale configuration keys in AppControllerTest for consistency
- Refactor pagination element
- Add breadcrumbs element and integrate it into the page header
- Fix title translation fallback in sidebar menu
- Enhance CSS variables for light and dark themes; improve button and table styles

## [1.1.1] - 2025-12-01

### Added
- **ButtonHelper**: Added `view()` method for view buttons with info variant and eye icon
- **ButtonHelper**: Added `editCompact()` method for compact edit buttons (small, icon-only)
- **ButtonHelper**: Added `deleteCompact()` method for compact delete buttons (small, icon-only)
- **ButtonHelper**: Added `preview()` method for preview buttons with external link icon
- Additional tests for ButtonHelper covering all methods and options

## [1.1.0] - 2025-11-30

### Added
- Comprehensive helpers documentation (`docs/HELPERS.md`)
- Layout configuration for CSS, JavaScript, and font assets
- Configurable asset loading with `cssDefaults`, `css`, `scriptDefaults`, `script` options
- Google Fonts configuration with preconnect support
- Layout elements for rendering assets (`Layout/css.php`, `Layout/script.php`)
- Comprehensive configuration documentation (`docs/CONFIGURATION.md`)

### Changed
- Improve pagination element
- Refactor page header and content layout and styles for improved responsiveness
- Move configuration documentation from README to `docs/CONFIGURATION.md`
- Default layout now reads CSS and JS assets from configuration

### Removed
- **CardHelper**: Moved to [brammo/bootstrap-ui](https://github.com/brammo/bootstrap-ui)
- **TableHelper**: Moved to [brammo/bootstrap-ui](https://github.com/brammo/bootstrap-ui)
- **DescriptionHelper**: Moved to [brammo/bootstrap-ui](https://github.com/brammo/bootstrap-ui)
- **NavHelper**: Moved to [brammo/bootstrap-ui](https://github.com/brammo/bootstrap-ui)

### Changed
- Updated ButtonHelper documentation in `docs/HELPERS.md` to include all preset button methods

## [1.0.0] - 2025-11-25

### Added

#### Core Features
- Initial release of Brammo Admin plugin for CakePHP 5
- Plugin bootstrap with CakePHP Authentication middleware integration
- User profile management controller
- Default admin layout with Bootstrap UI integration

#### View Helpers
- **ButtonHelper**: Generate Bootstrap-styled buttons with variants, icons, and sizes
  - Support for GET and POST methods
  - Built-in helpers for create, edit, and delete actions
  - Confirmation dialog support
- **CardHelper**: Create Bootstrap card components with headers and footers
- **TableHelper**: Build responsive HTML tables with headers and rows
- **DescriptionHelper**: Generate description lists with term-definition pairs

#### Configuration
- Internationalization (i18n) support with configurable default locale
- Customizable sidebar menu with icons and nested submenus
- Brand customization (name and HTML logo)
- Home link configuration for navigation
- Icon defaults configuration for Bootstrap Icons

#### Testing
- Comprehensive test suite with 45 tests and 120 assertions
- PHPUnit 10 integration
- Test fixtures for user data
- Unit tests for all view helpers
- Controller integration tests
- Plugin bootstrap tests

#### Quality Assurance
- **PHPStan** level 8 (strictest) static analysis
- **Psalm** level 1 (strictest) static analysis with baseline
- CakePHP CodeSniffer integration
- Full type hints on all methods and properties
- Automated quality checks via `composer check`

#### Documentation
- Comprehensive README with installation and usage guide
- PHPStan configuration documentation
- Psalm configuration documentation
- Code examples for all view helpers
- Contributing guidelines

#### Localization
- Bulgarian translation (bg) support
- Translation infrastructure for additional languages

#### Assets
- Custom CSS for admin interface
- JavaScript for admin functionality
- Bootstrap UI integration

### Dependencies
- PHP >= 8.1
- CakePHP >= 5.0
- CakePHP Authentication >= 3.0
- Brammo Auth >= 1.0
- Bootstrap UI >= 5.0

### Development Tools
- PHPUnit >= 10.0
- PHPStan >= 1.10
- Psalm >= 5.26
- CakePHP CodeSniffer >= 5.0

[1.0.0]: https://github.com/brammo/admin/releases/tag/v1.0.0
[1.1.0]: https://github.com/brammo/admin/releases/tag/v1.1.0
[1.1.1]: https://github.com/brammo/admin/releases/tag/v1.1.1
[1.2.0]: https://github.com/brammo/admin/releases/tag/v1.2.0
[1.3.0]: https://github.com/brammo/admin/releases/tag/v1.3.0
[1.4.0]: https://github.com/brammo/admin/releases/tag/v1.4.0
[1.5.0]: https://github.com/brammo/admin/releases/tag/v1.5.0
[Unreleased]: https://github.com/brammo/admin/compare/v1.5.0...HEAD
