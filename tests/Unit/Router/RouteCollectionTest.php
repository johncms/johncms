<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\RouteCollection;
use Johncms\Router\RouteRequirements;
use PHPUnit\Framework\TestCase;

final class RouteCollectionTest extends TestCase
{
    public function testCompileSortsRoutesByPriorityAndUsesAutoNamesForUnnamedRoutes(): void
    {
        $collection = new RouteCollection();

        $collection->get('/first', 'first_handler')->priority(10);
        $collection->get('/second', 'second_handler')->priority(100);

        $compiled = $collection->compile()->all();

        self::assertSame('legacy_route_2', array_key_first($compiled));
        self::assertSame('legacy_route_1', array_key_last($compiled));
    }

    public function testCompileAppliesGroupPrefixAndNamePrefix(): void
    {
        $collection = new RouteCollection(new RouteRequirements());

        $collection
            ->group('/guestbook', static function (RouteCollection $group): void {
                $group->get('/edit/{id:number}', 'edit_handler')->setName('edit');
                $group->get('/reply/{id:number}', 'reply_handler')->setName('reply')->priority(50);
            })
            ->setNamePrefix('guestbook.');

        $compiled = $collection->compile()->all();

        self::assertArrayHasKey('guestbook.reply', $compiled);
        self::assertArrayHasKey('guestbook.edit', $compiled);
        self::assertSame('/guestbook/reply/{id}', $compiled['guestbook.reply']->getPath());
        self::assertSame('/guestbook/edit/{id}', $compiled['guestbook.edit']->getPath());
        self::assertSame('\d+', $compiled['guestbook.reply']->getRequirement('id'));
        self::assertSame('\d+', $compiled['guestbook.edit']->getRequirement('id'));
    }

    public function testCollectionMiddlewaresArePrependedToRoutes(): void
    {
        $collection = (new RouteCollection())->addMiddleware('group_middleware');
        $collection->get('/test', 'handler')->addMiddleware('route_middleware')->setName('test.route');

        $compiledRoute = $collection->compile()->get('test.route');

        self::assertNotNull($compiledRoute);
        self::assertSame(['group_middleware', 'route_middleware'], $compiledRoute->getDefault('_middlewares'));
    }

    public function testNestedGroupAppliesOwnMiddlewaresToRoutes(): void
    {
        $collection = (new RouteCollection())
            ->addMiddleware('root_middleware');

        $collection
            ->group('/api', static function (RouteCollection $group): void {
                $group->addMiddleware('group_middleware');
                $group->get('/items', 'items_handler')
                    ->addMiddleware('route_middleware')
                    ->setName('api.items');
            });

        $compiledRoute = $collection->compile()->get('api.items');

        self::assertNotNull($compiledRoute);
        self::assertSame('/api/items', $compiledRoute->getPath());
        self::assertSame(['group_middleware', 'route_middleware'], $compiledRoute->getDefault('_middlewares'));
    }
}
