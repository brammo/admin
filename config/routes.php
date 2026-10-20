<?php
/**
 * Routes configuration
 * 
 * @var \Cake\Routing\RouteBuilder $routes
 */

use Cake\Routing\RouteBuilder;

$routes->plugin(
    'Brammo/Admin',
    ['path' => '/admin'],
    function (RouteBuilder $routeBuilder) {
        
        /**
         * Authentication and profile.
         * UserController remains available for hosts that route to controller User.
         */
        $routeBuilder->connect('/login', ['controller' => 'Users', 'action' => 'login']);
        $routeBuilder->connect('/logout', ['controller' => 'Users', 'action' => 'logout']);
        $routeBuilder->connect('/profile', ['controller' => 'Users', 'action' => 'profile']);
        $routeBuilder->connect('/user/profile', ['controller' => 'User', 'action' => 'profile']);

        /**
         * File Manager route
         */
        $routeBuilder->connect('/filemanager', ['controller' => 'FileManager', 'action' => 'index']);
        $routeBuilder->connect('/filemanager/{action}/*', ['controller' => 'FileManager']);
    }
);
