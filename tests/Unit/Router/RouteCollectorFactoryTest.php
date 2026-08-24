<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Modules\FilesystemModuleRepository;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleStateRecord;
use Johncms\Modules\ModuleStateStore;
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

    /**
     * A module that is switched off answers nothing. Its routes are what would otherwise reach a
     * controller that is no longer a service and a template whose namespace is gone — a 500 where
     * the honest answer is that the page does not exist.
     */
    public function testTheRoutesOfASwitchedOffModuleAreNotCollected(): void
    {
        $stateFile = sys_get_temp_dir() . DS . 'johncms-routes-' . uniqid() . '.php';
        $store = new ModuleStateStore($stateFile);
        $store->save(['johncms/forum' => new ModuleStateRecord('johncms/forum', 'forum', enabled: false)]);

        $registry = new ModuleRegistry(
            new FilesystemModuleRepository(),
            $store,
            ['johncms/forum', 'johncms/guestbook'],
        );

        $routes = (new RouteCollectorFactory($registry))($this->container);

        @unlink($stateFile);

        self::assertNull($this->findRouteByPath($routes, '/forum/download-file/{id}'));
        self::assertNotNull($this->findRouteByPath($routes, '/guestbook/clean'));
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
