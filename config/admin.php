<?php
/**
 * Admin configuration
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
            'icon' => 'house-door'
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
