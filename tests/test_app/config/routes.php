<?php
declare(strict_types=1);

use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    $routes->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
};
