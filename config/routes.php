<?php
declare(strict_types=1);

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder) {
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'home']);

        $builder->connect('/login',    ['controller' => 'Users', 'action' => 'login']);
        $builder->connect('/logout',   ['controller' => 'Users', 'action' => 'logout']);
        $builder->connect('/register', ['controller' => 'Users', 'action' => 'register']);

        $builder->connect(
            '/u/{username}',
            ['controller' => 'Users', 'action' => 'view'],
            ['pass' => ['username']]
        );

        $builder->connect(
            '/games/{slug}',
            ['controller' => 'Games', 'action' => 'play'],
            ['pass' => ['slug']]
        );
        $builder->connect(
            '/games/{slug}/new',
            ['controller' => 'Games', 'action' => 'newGame', '_method' => 'POST'],
            ['pass' => ['slug']]
        );
        $builder->connect(
            '/games/{slug}/move',
            ['controller' => 'Games', 'action' => 'move', '_method' => 'POST'],
            ['pass' => ['slug']]
        );

        $builder->connect(
            '/games/{slug}/rooms',
            ['controller' => 'Rooms', 'action' => 'lobby'],
            ['pass' => ['slug']]
        );
        $builder->connect(
            '/games/{slug}/rooms/create',
            ['controller' => 'Rooms', 'action' => 'create', '_method' => 'POST'],
            ['pass' => ['slug']]
        );
        $builder->connect(
            '/rooms/{code}',
            ['controller' => 'Rooms', 'action' => 'view'],
            ['pass' => ['code']]
        );
        $builder->connect(
            '/rooms/{code}/join',
            ['controller' => 'Rooms', 'action' => 'join', '_method' => 'POST'],
            ['pass' => ['code']]
        );
        $builder->connect(
            '/rooms/{code}/state',
            ['controller' => 'Rooms', 'action' => 'state'],
            ['pass' => ['code']]
        );
        $builder->connect(
            '/rooms/{code}/move',
            ['controller' => 'Rooms', 'action' => 'move', '_method' => 'POST'],
            ['pass' => ['code']]
        );

        $builder->fallbacks();
    });
};
