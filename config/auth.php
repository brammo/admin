<?php
/**
 * Auth configuration
 * 
 * @see https://github.com/brammo/auth/blob/master/README.md#configuration
 */

return [

    'Auth' => [

        'Authentication' => [
            'sessionKey' => 'AdminAuth',
            'cookieName' => 'AdminCookieAuth',
        ],

        'Routes' => [
            'login' => '/admin/login',
            'logout' => '/admin/logout',
            'loginRedirect' => '/admin',
        ],

        'Templates' => [
            'login' => 'Brammo/Admin.User/login',
        ],
    ],
];
