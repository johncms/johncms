<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\RouteCollectorFactory;
use Johncms\System\Users\User;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

final class RouteCollectorFactoryTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $user = new User();
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('get')->with(User::class)->willReturn($user);
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

    public function testLocalRoutesAreLoadedWhenFileExists(): void
    {
        $localRoutesFile = CONFIG_PATH . 'routes.local.php';
        $content = <<<'PHP'
            <?php
            declare(strict_types=1);
            use Johncms\Router\RouteCollection;
            use Johncms\System\Users\User;
            return static function (RouteCollection $router, User $user): void {
                $router->get('/test-local-route', 'modules/test/index.php');
            };
            PHP;

        file_put_contents($localRoutesFile, $content);

        try {
            $this->expectUserDeprecationMessageMatches('/config\/routes\.local\.php is deprecated/');
            $routes = (new RouteCollectorFactory())($this->container);
            $localRoute = $this->findRouteByPath($routes, '/test-local-route');
            self::assertNotNull($localRoute, 'Route from routes.local.php should be loaded');
        } finally {
            unlink($localRoutesFile);
        }
    }

    public function testNoErrorWhenLocalRoutesFileMissing(): void
    {
        $localRoutesFile = CONFIG_PATH . 'routes.local.php';
        self::assertFileDoesNotExist($localRoutesFile);

        $routes = (new RouteCollectorFactory())($this->container);
        self::assertInstanceOf(RouteCollection::class, $routes);
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
