<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testCompile()
    {
        $route = (new Route('post', '/admin/{page}', fn() => '100'))
            ->setDefaults(['page' => 1000])
            ->addMiddleware('test')
            ->setName('testRoute')
            ->setPriority(999)
            ->setRequirements(['test' => '\d+']);

        $compiledRoute = $route->compile();

        $actualParams = [
            'path'         => $compiledRoute->getPath(),
            'page'         => $compiledRoute->getDefault('page'),
            'method'       => $compiledRoute->getMethods()[0],
            'name'         => $compiledRoute->getDefault('_name'),
            'middleware'   => $compiledRoute->getDefault('_middlewares'),
            'controller'   => is_callable($compiledRoute->getDefault('_controller')),
            'requirements' => $compiledRoute->getRequirement('test'),
            'priority'     => $route->getPriority(),
        ];

        self::assertEquals(
            [
                'path'         => '/admin/{page}',
                'page'         => 1000,
                'method'       => 'POST',
                'name'         => 'testRoute',
                'middleware'   => [0 => 'test'],
                'controller'   => true,
                'requirements' => '\d+',
                'priority'     => 999,
            ],
            $actualParams,
            'test route'
        );
    }
}
