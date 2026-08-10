<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\Route;
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

    /**
     * The module of a route is what the kernel enters the module context from, so it has to reach
     * routes declared directly and routes declared inside a group alike.
     */
    public function testRoutesCarryTheModuleTheyWereDeclaredFor(): void
    {
        $collection = (new RouteCollection())->setModule('forum');
        $collection->get('/forum', 'handler')->setName('forum.index');
        $collection->group('/forum', static function (RouteCollection $group): void {
            $group->get('/rules', 'rules_handler')->setName('forum.rules');
        });

        $compiled = $collection->compile();

        self::assertSame('forum', $compiled->get('forum.index')?->getDefault('_module'));
        self::assertSame('forum', $compiled->get('forum.rules')?->getDefault('_module'));
    }

    public function testRoutesOfTheCoreCarryNoModule(): void
    {
        $collection = new RouteCollection();
        $collection->get('/', 'handler')->setName('home');

        self::assertNull($collection->compile()->get('home')?->getDefault('_module'));
    }

    /**
     * Unlike the middlewares, the exemption reaches the groups declared inside the collection: an
     * exempt /api prefix that silently protected its nested groups again would be a trap.
     */
    public function testWithoutCsrfAppliesToOwnRoutesAndNestedGroups(): void
    {
        $collection = (new RouteCollection())->withoutCsrf();
        $collection->post('/api/items', 'items_handler')->setName('api.items');
        $collection->group('/api/v2', static function (RouteCollection $group): void {
            $group->post('/items', 'v2_items_handler')->setName('api.v2.items');
        });

        $compiled = $collection->compile();

        self::assertTrue($compiled->get('api.items')?->getDefault(Route::CSRF_EXEMPT_ATTRIBUTE));
        self::assertTrue($compiled->get('api.v2.items')?->getDefault(Route::CSRF_EXEMPT_ATTRIBUTE));
    }

    public function testRoutesOfACollectionAreProtectedByDefault(): void
    {
        $collection = new RouteCollection();
        $collection->post('/guestbook', 'handler')->setName('guestbook.post');

        $defaults = $collection->compile()->get('guestbook.post')?->getDefaults() ?? [];

        self::assertArrayNotHasKey(Route::CSRF_EXEMPT_ATTRIBUTE, $defaults);
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
