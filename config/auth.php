<?php
/**
 * Authentication configuration
 *
 * Defaults live under Admin.Auth. Existing Admin.Auth or legacy Auth
 * values are merged on top so application configuration wins.
 *
 * @see docs/CONFIGURATION.md
 */

use Cake\Core\Configure;
use Cake\Utility\Hash;

$config = [
    'Users' => [
        /**
         * Table used to manage users.
         * Expected fields include email and password.
         */
        'table' => 'Brammo/Admin.Users',

        /**
         * Controller that handles login, logout, and profile.
         */
        'controller' => 'Brammo/Admin.Users',
    ],

    'Routes' => [
        'login' => '/admin/login',
        'logout' => '/admin/logout',
        'loginRedirect' => '/admin',
    ],

    'Authentication' => [
        /**
         * Finder used when looking up users. "active" allows only active accounts.
         */
        'finder' => 'active',

        'fields' => [
            'username' => 'email',
            'password' => 'password',
        ],

        'passwordHasher' => [
            'className' => 'Authentication.Default',
        ],

        /**
         * Rehash passwords on login when the hasher cost or algorithm changed.
         */
        'rehashPasswords' => false,

        'sessionKey' => 'AdminAuth',

        'cookieName' => 'AdminCookieAuth',

        'rememberMeField' => 'remember_me',
    ],

    'Templates' => [
        'login' => 'Brammo/Admin.User/login',
    ],

    'Messages' => [
        'invalidCredentials' => 'Invalid email or password',
        'blocked' => 'Your account has been blocked',
        'notActivated' => 'Your account is not yet activated',
        'enumerateAccounts' => true,
    ],
];

$overrides = Configure::read('Admin.Auth');
if (!is_array($overrides)) {
    $overrides = Configure::read('Auth');
}
if (is_array($overrides)) {
    $config = Hash::merge($config, $overrides);
}

return [
    'Admin' => [
        'Auth' => $config,
    ],
];
