# Configuration

All configuration options are stored under the `Admin` key in your application's configuration.

## Internationalization

Set the default language for the admin panel:

```php
'Admin' => [
    'I18n' => [
        'default' => 'bg' // or any other locale
    ]
]
```

## Brand

Customize the brand name and logo displayed in the admin panel:

```php
'Admin' => [
    'Brand' => [
        'name' => 'My Admin',
        'html' => '<span class="fs-4 fw-bold">My<span class="text-primary">Admin</span></span>'
    ]
]
```

## Home Link

Configure the home link used in the brand and breadcrumbs:

```php
'Admin' => [
    'Home' => [
        'title' => __('Home'),
        'url' => '/admin',
        'icon' => 'house-door'
    ]
]
```

## Sidebar Menu

The sidebar menu can be customized with icons, links, and optional submenus.

### Configuration

```php
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

### Menu Item Structure

Each menu item can have the following properties:

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `title` | string | Yes | Display text - use `__('Title')` or `__d('admin', 'Title')` |
| `icon` | string/array | No | Bootstrap Icons name or icon config array |
| `url` | array | No | CakePHP URL array, defaults to `['plugin' => 'Admin', 'controller' => name, 'action' => 'index']` |
| `submenu` | array | No | Array of submenu items with the same structure |

### Icon Configuration

Icons use Bootstrap Icons by default. You can customize the icon defaults:

| Option | Default | Description |
|--------|---------|-------------|
| `tag` | `i` | HTML tag for icons |
| `namespace` | `bi` | CSS namespace |
| `prefix` | `bi` | CSS class prefix |
| `size` | `null` | Icon size class (`sm`, `lg`, etc.) |

#### Using FontAwesome

To use FontAwesome icons globally, change iconDefaults:

```php
'iconDefaults' => [
    'prefix' => 'fa',
    'namespace' => 'fa-solid'
]
```

Or set per-icon:

```php
'Dashboard' => [
    'title' => __('Dashboard'),
    'icon' => [
        'name' => 'gauge',
        'prefix' => 'fa',
        'namespace' => 'fa-solid'
    ]
],
```

## Layout Assets

Configure CSS, JavaScript, and font assets for the admin panel.

### Configuration Structure

```php
'Admin' => [
    'Layout' => [
        'cssDefaults' => [...],    // Default CSS assets (Bootstrap)
        'css' => [],               // Additional CSS to append
        'scriptDefaults' => [...], // Default JS assets (Bootstrap)
        'script' => [],            // Additional JS to append
        'fonts' => [...],          // Google Fonts configuration
    ],
]
```

### CSS Assets

#### Default CSS (`cssDefaults`)

Bootstrap CSS and Bootstrap Icons are loaded by default. These assets are always rendered first.

#### Custom CSS (`css`)

Add additional CSS assets that will be appended after the defaults.

Each entry can be:

1. **String URL** - Simple URL to a CSS file:
   ```php
   'css' => [
       'https://example.com/custom.css',
   ],
   ```

2. **HTML Tag** - Raw link tag (will be output as-is):
   ```php
   'css' => [
       '<link rel="stylesheet" href="https://example.com/extra.css">',
   ],
   ```

3. **Array with options** - URL with integrity and crossorigin attributes:
   ```php
   'css' => [
       [
           'url' => 'https://example.com/secure.css',
           'integrity' => 'sha384-...',
           'crossorigin' => 'anonymous',
       ],
   ],
   ```

### JavaScript Assets

#### Default Scripts (`scriptDefaults`)

Bootstrap Bundle JS is loaded by default. These assets are always rendered first.

#### Custom Scripts (`script`)

Add additional JavaScript assets that will be appended after the defaults.

Each entry can be:

1. **String URL** - Simple URL to a JS file:
   ```php
   'script' => [
       'https://example.com/custom.js',
   ],
   ```

2. **HTML Tag** - Raw script tag (will be output as-is):
   ```php
   'script' => [
       '<script src="https://example.com/extra.js"></script>',
   ],
   ```

3. **Array with options** - URL with integrity and crossorigin attributes:
   ```php
   'script' => [
       [
           'url' => 'https://example.com/secure.js',
           'integrity' => 'sha384-...',
           'crossorigin' => 'anonymous',
       ],
   ],
   ```

### Fonts Configuration

Configure Google Fonts or other web fonts:

```php
'fonts' => [
    'enabled' => true,
    'preconnect' => [
        'https://fonts.googleapis.com',
        'https://fonts.gstatic.com',
    ],
    'files' => [
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
    ],
],
```

| Key | Type | Description |
|-----|------|-------------|
| `enabled` | bool | Enable/disable font loading |
| `preconnect` | array | URLs to preconnect for faster loading |
| `files` | array | Font stylesheet URLs to load |

### Usage Examples

#### Adding Custom Styles

In your project's configuration file (e.g., `config/app_local.php`):

```php
use Cake\Core\Configure;

Configure::write('Admin.Layout.css', [
    '/css/admin-custom.css',
    'https://cdn.example.com/theme.css',
]);
```

#### Adding Custom Scripts

```php
Configure::write('Admin.Layout.script', [
    '/js/admin-custom.js',
    [
        'url' => 'https://cdn.example.com/analytics.js',
        'crossorigin' => 'anonymous',
    ],
]);
```

#### Disabling Google Fonts

```php
Configure::write('Admin.Layout.fonts.enabled', false);
```

#### Using Different Fonts

```php
Configure::write('Admin.Layout.fonts', [
    'enabled' => true,
    'preconnect' => [
        'https://fonts.googleapis.com',
        'https://fonts.gstatic.com',
    ],
    'files' => [
        'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap',
    ],
]);
```

### Asset Loading Order

Assets are loaded in the following order:

1. **CSS**:
   - `cssDefaults` (Bootstrap CSS, Bootstrap Icons)
   - `css` (your custom CSS)
   - `fonts` (Google Fonts if enabled)
   - Plugin styles (`Brammo/Admin.styles`)
   - View-specific CSS blocks

2. **JavaScript**:
   - `scriptDefaults` (Bootstrap Bundle)
   - `script` (your custom scripts)
   - Plugin scripts (`Brammo/Admin.main`)
   - View-specific script blocks
