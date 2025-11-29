# Brammo Admin Plugin

Admin dashboard plugin for CakePHP 5 with Bootstrap UI integration.

## Requirements

- PHP 8.1 or higher
- CakePHP 5.0 or higher
- CakePHP Authentication plugin 3.0 or higher

## Installation

Install via Composer:

```bash
composer require brammo/admin
```

## Loading the Plugin

Load the plugin in your application's `src/Application.php`:

```php
public function bootstrap(): void
{
    parent::bootstrap();
    
    $this->addPlugin('Brammo/Admin');
}
```

## Features

### View Helpers

The plugin provides several view helpers to simplify UI development:

- **ButtonHelper** - Generate Bootstrap-styled buttons with icons
- **CardHelper** - Create Bootstrap card components
- **TableHelper** - Build responsive HTML tables
- **DescriptionHelper** - Generate description lists

See [docs/HELPERS.md](docs/HELPERS.md) for detailed documentation and examples.

### Controllers

#### UserController

Provides user profile management:

- `profile()` - View and edit user profile

### Authentication

The plugin integrates with CakePHP Authentication and Brammo Auth plugin for user management and authentication.

## Configuration

All configuration options are documented in [docs/CONFIGURATION.md](docs/CONFIGURATION.md), including:

- Internationalization
- Brand customization
- Home link
- Sidebar menu
- Layout assets (CSS, JavaScript, fonts)

## Development

### Running Tests

```bash
# Run all tests
composer test

# Run with verbose output
vendor/bin/phpunit --testdox
```

### Static Analysis

The project uses both PHPStan and Psalm for static code analysis:

```bash
# Run PHPStan (level 8)
composer stan

# Run Psalm (level 1)
composer psalm

# Run all checks (tests + static analysis)
composer check
```

See [docs/PHPSTAN.md](docs/PHPSTAN.md) and [docs/PSALM.md](docs/PSALM.md) for detailed documentation.

### Code Quality

- **PHPStan Level**: 8 (strictest)
- **Psalm Level**: 1 (strictest)
- **Test Coverage**: 45 tests, 120 assertions
- **PHP Version**: 8.1+

## Directory Structure

```
brammo/admin/
├── config/              # Plugin configuration
├── docs/                # Documentation
├── resources/           # Resources (translations, etc.)
│   └── locales/         # Translation files
├── src/                 # Source code
│   ├── Controller/      # Controllers
│   ├── Model/           # Models (Entity, Table)
│   ├── View/            # View classes and helpers
│   │   └── Helper/      # View helpers
│   └── Plugin.php       # Plugin bootstrap
├── templates/           # Templates
│   ├── element/         # Template elements
│   ├── layout/          # Layouts
│   └── User/            # User views
├── tests/               # Test suite
│   ├── Fixture/         # Test fixtures
│   └── TestCase/        # Test cases
└── webroot/             # Public assets
    ├── css/             # Stylesheets
    └── js/              # JavaScript files
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests and static analysis (`composer check`)
5. Commit your changes (`git commit -am 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Create a Pull Request

### Code Standards

- Follow PSR-12 coding standards
- Write tests for new features
- Ensure all tests pass
- Maintain PHPStan level 8 and Psalm level 1 compliance
- Add type hints to all methods

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Author

**Roman Sidorkin**
- Email: roman.sidorkin@gmail.com
- GitHub: [@brammo](https://github.com/brammo)

## Related Projects

- [brammo/auth](https://github.com/brammo/auth) - Authentication plugin for CakePHP
