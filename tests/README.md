# Tests

This directory contains the test suite for the Brammo/Admin plugin.

## Running Tests

To run all tests:

```bash
vendor/bin/phpunit
```

To run a specific test file:

```bash
vendor/bin/phpunit tests/TestCase/PluginTest.php
```

To run tests with coverage:

```bash
vendor/bin/phpunit --coverage-html coverage/
```

## Test Structure

- `tests/bootstrap.php` - Test bootstrap file
- `tests/TestCase/` - Test cases organized by component type
  - `PluginTest.php` - Plugin class tests
  - `Controller/` - Controller tests
    - `AppControllerTest.php` - Base controller tests
    - `UserControllerTest.php` - User controller tests
  - `View/Helper/` - View helper tests
    - `ButtonHelperTest.php` - Button helper tests
    - `CardHelperTest.php` - Card helper tests
    - `TableHelperTest.php` - Table helper tests
    - `DescriptionHelperTest.php` - Description helper tests
- `tests/Fixture/` - Test fixtures for database testing

## Test Coverage

The test suite covers:

- Plugin initialization and configuration
- Controller functionality and authentication
- View helpers for rendering UI components
- Integration tests for user profile management

## Adding New Tests

When adding new features, make sure to:

1. Create corresponding test files in the appropriate directory
2. Follow the existing naming conventions (`*Test.php`)
3. Use PHPUnit assertions and CakePHP testing utilities
4. Add fixtures if database testing is required
5. Run the full test suite to ensure no regressions

## Dependencies

Testing dependencies are defined in `composer.json`:

- `phpunit/phpunit` - Testing framework
- `cakephp/cakephp-codesniffer` - Code style checking
