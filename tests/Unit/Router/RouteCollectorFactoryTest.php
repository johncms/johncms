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
    public function testInvokeBuildsCompiledCollectionFromConfigRoutes(): void
    {
        $user = new User();
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('get')
            ->with(User::class)
            ->willReturn($user);

        $factory = new RouteCollectorFactory();
        $routes = $factory($container);

        self::assertInstanceOf(RouteCollection::class, $routes);
        self::assertGreaterThan(0, $routes->count());

        $forumDownloadRoute = $this->findRouteByPath($routes, '/forum/download-file/{id}');
        self::assertNotNull($forumDownloadRoute);
        self::assertSame('\d+', $forumDownloadRoute->getRequirement('id'));

        $guestbookCleanRoute = $this->findRouteByPath($routes, '/guestbook/clean');
        self::assertNotNull($guestbookCleanRoute);
        self::assertSame(
            [\Johncms\Modules\Guestbook\Application\Middlewares\GuestbookCleanAccessMiddleware::class],
            $guestbookCleanRoute->getDefault('_middlewares')
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
