<?php
/**
 * Admin configuration
 * 
 * @see docs/CONFIGURATION.md for detailed configuration options
 */

return [

    'Admin' => [

        /* 
         * Default locale for the admin panel
         * 
         * Uses the I18n package format
         */
        'I18n' => [
            'default' => 'en_US',
        ],

        /**
         * Layout configuration
         * 
         * Configure CSS, JavaScript and font assets for the admin panel.
         * 
         * @see docs/CONFIGURATION.md for detailed options
         */
        'Layout' => [

            /**
             * Default CSS assets (Bootstrap and Icons)
             */
            'cssDefaults' => [
                [
                    'url' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
                    'integrity' => 'sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH',
                    'crossorigin' => 'anonymous',
                ],
                [
                    'url' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css',
                    'integrity' => 'sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD',
                    'crossorigin' => 'anonymous',
                ],
            ],

            /**
             * Additional CSS assets to append
             */
            'css' => [],

            /**
             * Default JavaScript assets (Bootstrap)
             */
            'scriptDefaults' => [
                [
                    'url' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
                    'integrity' => 'sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz',
                    'crossorigin' => 'anonymous',
                ],
            ],

            /**
             * Additional JavaScript assets to append
             */
            'script' => [],

            /**
             * Google Fonts configuration
             */
            'fonts' => [
                'enabled' => true,
                'preconnect' => [
                    'https://fonts.googleapis.com',
                    'https://fonts.gstatic.com',
                ],
                'files' => [
                    'https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap',
                ],
            ],
        ],

        /**
         * Brand configuration
         */
        'Brand' => [
            /**
             * The name of the brand displayed in the admin panel
             */
            'name' => 'Brammo Admin',

            /**
             * Optional HTML content for the brand logo
             */
            'html' => '<span class="fs-4 text-secondary fw-bold">Brammo<span class="text-primary">Admin</span></span>'
        ],

        /**
         * Home link configuration
         * Used for the brand link and the breadcrumb home link
         */
        'Home' => [
            'title' => __d('brammo/admin', 'Home'),
            'url' => '/admin',
            'icon' => 'speedometer2',
        ],

        /**
         * Sidebar configuration
         */
        'Sidebar' => [

            'iconDefaults' => [
                'tag' => 'i',
                'namespace' => 'bi',
                'prefix' => 'bi',
                'size' => null,
            ],

            /**
             * Sidebar menu configuration
             * 
             * @see templates/element/Sidebar/menu.php
             * 
             * Each menu item have a name and the following keys:
             * - title: The display title of the menu item, defaults to the name translated
             * - icon: The icon name (Bootstrap Icons), optional
             * - url: The URL array for the menu item, defaults to ['plugin' => 'Admin', 'controller' => name, 'action' => 'index']
             * - submenu: An array of submenu items (same structure as menu items)
             * 
             * Example:
             * ```php
             * 'Dashboard' => [
             *     'title' => __d('admin', 'Dashboard'),
             *     'icon' => 'speedometer2',
             *     'url' => [
             *         'plugin' => 'Admin',
             *         'controller' => 'Dashboard',
             *         'action' => 'index'
             *     ],
             * ]
             * ```
             */
            'menu' => [
            ],
        ],
    ],
];
