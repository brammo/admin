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

#### ButtonHelper

Generate Bootstrap-styled buttons with ease:

```php
// Basic buttons
echo $this->Button->link('View', ['action' => 'view', $id]);
echo $this->Button->create(['action' => 'add']);
echo $this->Button->edit(['action' => 'edit', $id]);
echo $this->Button->delete(['action' => 'delete', $id]);

// Custom buttons
echo $this->Button->render('Custom', '/url', [
    'variant' => 'primary',
    'icon' => 'plus-circle',
    'size' => 'sm'
]);
```

#### CardHelper

Create Bootstrap card components:

```php
echo $this->Card->render($content, [
    'header' => 'Card Header',
    'footer' => 'Card Footer',
    'class' => ['custom-class']
]);
```

#### TableHelper

Build HTML tables with headers and rows:

```php
$this->Table->header(['ID', 'Name', 'Email']);
$this->Table->row([1, 'John Doe', 'john@example.com']);
$this->Table->row([2, 'Jane Smith', 'jane@example.com']);
echo $this->Table->render();
```

#### DescriptionHelper

Generate description lists:

```php
echo $this->Description
    ->add('Name', 'John Doe')
    ->add('Email', 'john@example.com')
    ->add('Phone', '+1234567890')
    ->render();
```

### Controllers

#### UserController

Provides user profile management:

- `profile()` - View and edit user profile

### Authentication

The plugin integrates with CakePHP Authentication and Brammo Auth plugin for user management and authentication.

## Configuration

### Internationalization

Set the default language in your configuration:

```php
// config/app.php
'Admin' => [
    'I18n' => [
        'default' => 'bg' // or any other locale
    ]
]
```

### Sidebar Menu

The sidebar menu can be customized in your application configuration. Add menu items with icons, links, and optional submenus.

#### Configuration

Configure the sidebar menu in your `config/app.php`:

```php
// config/app.php
'Admin' => [
    'Sidebar' => [
        'iconDefaults' => [
            'tag' => 'i',
            'namespace' => 'bi',  // Bootstrap Icons
            'prefix' => 'bi',
            'size' => null,
        ],
        
        'menu' => [
            'Dashboard' => [
                'title' => __('Dashboard'),
                'icon' => 'speedometer2',
                'url' => [
                    'plugin' => 'Admin',
                    'controller' => 'Dashboard',
                    'action' => 'index'
                ],
            ],
            'Users' => [
                'title' => __('Users'),
                'icon' => 'people',
                'url' => [
                    'plugin' => 'Admin',
                    'controller' => 'Users',
                    'action' => 'index'
                ],
            ],
            'Settings' => [
                'title' => __('Settings'),
                'icon' => 'gear',
                'submenu' => [
                    'General' => [
                        'title' => __('General'),
                        'url' => [
                            'plugin' => 'Admin',
                            'controller' => 'Settings',
                            'action' => 'general'
                        ],
                    ],
                    'Security' => [
                        'title' => __('Security'),
                        'url' => [
                            'plugin' => 'Admin',
                            'controller' => 'Settings',
                            'action' => 'security'
                        ],
                    ],
                ],
            ],
        ],
    ],
]
```

#### Menu Item Structure

Each menu item can have the following properties:

- **title**: Display text (required) - use translation function  `__('Title')` or `__d('admin', 'Title')` for plugin
- **icon**: Bootstrap Icons name (optional) - see [Bootstrap Icons](https://icons.getbootstrap.com/)
- **url**: CakePHP URL array (optional) - defaults to `['plugin' => 'Admin', 'controller' => name, 'action' => 'index']`
- **submenu**: Array of submenu items with the same structure (optional)

#### Icon Configuration

Icons use Bootstrap Icons by default. You can customize the icon defaults:

- **tag**: HTML tag for icons (default: `i`)
- **namespace**: CSS namespace (default: `bi`)
- **prefix**: CSS class prefix (default: `bi`)
- **size**: Icon size class like `sm` or `lg` (optional)

if you want to use FontAwesome icon, change iconDefaults to:

```php
[
    'iconDefaults' => [
        'prefix' => 'fa',
        'namespace' => 'fa-solid'
    ]
]
```

or set `prefix` and `namespace` for any icon

```php
[
    'Dashboard' => [
        'title' => __('Dashboard')
        'icon' => [
            'name' => 'gauge',
            'prefix' => 'fa',
            'namespace' => 'fa-solid'
        ]
    ],
]
```

### Layouts

The plugin uses the default layout `Brammo/Admin.default`. You can customize it by creating your own layout in `templates/layout/` directory.

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
