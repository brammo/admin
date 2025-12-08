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
         * User profile route
         */
        $routeBuilder->connect('/profile', ['controller' => 'User', 'action' => 'profile']);

        /**
         * File Manager route
         */
        $routeBuilder->connect('/filemanager', ['controller' => 'FileManager', 'action' => 'index']);
        $routeBuilder->connect('/filemanager/{action}/*', ['controller' => 'FileManager']);
    }
);
