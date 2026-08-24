<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\RouteCollectorFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

final class RouteCollectorFactoryTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        // Building the collection asks the container for nothing: the routes are the same for
        // every visitor, and what closes one is a middleware rather than a condition around it.
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->expects(self::never())->method('get');
    }

    public function testModuleRoutesAreLoaded(): void
    {
        $routes = (new RouteCollectorFactory())($this->container);

        self::assertInstanceOf(RouteCollection::class, $routes);
        self::assertGreaterThan(0, $routes->count());

        $forumDownloadRoute = $this->findRouteByPath($routes, '/forum/download-file/{id}');
        self::assertNotNull($forumDownloadRoute, 'Forum route should be loaded from module config');
        self::assertSame('\d+', $forumDownloadRoute->getRequirement('id'));

        $guestbookCleanRoute = $this->findRouteByPath($routes, '/guestbook/clean');
        self::assertNotNull($guestbookCleanRoute, 'Guestbook route should be loaded from module config');
        self::assertSame(
            [\Johncms\Modules\Guestbook\Application\Middlewares\GuestbookCleanAccessMiddleware::class],
            $guestbookCleanRoute->getDefault('_middlewares')
        );
    }

    /**
     * Every route a module declares is stamped with the key of that module — vendor and name,
     * taken from the path of the file declaring it. That is what the kernel enters the module
     * context from, and what tells it where the dictionaries of the module lie.
     */
    public function testModuleRoutesAreStampedWithTheirModule(): void
    {
        $routes = (new RouteCollectorFactory())($this->container);

        self::assertSame(
            'johncms/forum',
            $this->findRouteByPath($routes, '/forum/download-file/{id}')?->getDefault('_module')
        );
        self::assertSame(
            'johncms/guestbook',
            $this->findRouteByPath($routes, '/guestbook/clean')?->getDefault('_module')
        );
    }

    private function findRouteByPath(RouteCollection $routes, string $path): ?\Symfony\Component\Routing\Route
    {
        foreach ($routes->all() as $route) {
            if ($route->getPath() === $path) {
                return $route;
            }
        }

        return null;
    }
}
