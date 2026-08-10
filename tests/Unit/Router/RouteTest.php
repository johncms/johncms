<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\Route;
use Johncms\Router\RouteRequirements;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    public function testCompileBuildsSymfonyRouteWithDefaultsRequirementsAndMiddlewares(): void
    {
        $route = new Route(
            method: ['GET', 'POST'],
            path: '/forum/topic/{id:number}',
            handler: ['ForumController', 'viewTopic'],
            routeRequirements: new RouteRequirements(),
        );

        $route
            ->setName('forum.topic')
            ->priority(50)
            ->defaults(['foo' => 'bar'])
            ->requirements(['id' => '\d+'])
            ->addMiddleware('first_middleware')
            ->addMiddleware('second_middleware');

        $compiled = $route->compile();

        self::assertSame('/forum/topic/{id}', $compiled->getPath());
        self::assertSame(['GET', 'POST'], $compiled->getMethods());
        self::assertSame('\d+', $compiled->getRequirement('id'));
        self::assertSame('bar', $compiled->getDefault('foo'));
        self::assertSame(['ForumController', 'viewTopic'], $compiled->getDefault('_handler'));
        self::assertSame(['first_middleware', 'second_middleware'], $compiled->getDefault('_middlewares'));
        self::assertSame('forum.topic', $route->getName());
        self::assertSame(50, $route->getPriority());
    }

    public function testWithoutCsrfMarksTheCompiledRouteAsExempt(): void
    {
        $route = new Route(method: 'POST', path: '/webhook', handler: 'WebhookController');

        $compiled = $route->withoutCsrf()->compile();

        self::assertTrue($compiled->getDefault(Route::CSRF_EXEMPT_ATTRIBUTE));
    }

    public function testARouteIsProtectedByDefault(): void
    {
        $route = new Route(method: 'POST', path: '/guestbook', handler: 'GuestbookController');

        self::assertArrayNotHasKey(Route::CSRF_EXEMPT_ATTRIBUTE, $route->compile()->getDefaults());
    }

    public function testCompileDoesNotAddMiddlewaresDefaultWhenNoMiddlewareDefined(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/guestbook',
            handler: 'GuestbookController',
        );

        $compiled = $route->compile();
        $defaults = $compiled->getDefaults();

        self::assertArrayHasKey('_handler', $defaults);
        self::assertArrayNotHasKey('_middlewares', $defaults);
    }
}
