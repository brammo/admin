<?php
/**
 * Routes configuration
 */

use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    $routes->plugin(
        'Brammo/Admin',
        ['path' => '/admin'],
        function (RouteBuilder $builder) {
            
            /**
             * User profile route
             */
            $builder->connect('/profile', ['controller' => 'User', 'action' => 'profile']);
        }
    );
};
