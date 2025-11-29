# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Refactor page header and content layout and styles for improved responsiveness

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
