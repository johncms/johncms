<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\MiddlewareDispatcher;
use Johncms\Router\MiddlewareInterface;
use Johncms\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testDispatchRunsMiddlewaresInExpectedOrder(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $dispatcher = new MiddlewareDispatcher($container);
        $request = Request::create('/forum', 'GET');
        $executionLog = [];

        $first = static function (Request $request, callable $next) use (&$executionLog): mixed {
            $executionLog[] = 'first.before';
            $result = $next($request);
            $executionLog[] = 'first.after';
            return $result;
        };

        $second = static function (Request $request, callable $next) use (&$executionLog): mixed {
            $executionLog[] = 'second.before';
            $result = $next($request);
            $executionLog[] = 'second.after';
            return $result;
        };

        $result = $dispatcher->dispatch(
            request: $request,
            middlewares: [$first, $second],
            handler: static function (Request $request) use (&$executionLog): string {
                $executionLog[] = 'handler';
                return 'ok:' . $request->getMethod();
            },
        );

        self::assertSame('ok:GET', $result);
        self::assertSame(
            ['first.before', 'second.before', 'handler', 'second.after', 'first.after'],
            $executionLog,
        );
    }

    public function testDispatchResolvesClassStringMiddlewareFromContainer(): void
    {
        $request = Request::create('/', 'GET');
        $middleware = new class () implements MiddlewareInterface {
            public function handle(Request $request, callable $next): mixed
            {
                return 'class-string:' . $next($request);
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('get')
            ->with(TestContainerMiddleware::class)
            ->willReturn($middleware);

        $dispatcher = new MiddlewareDispatcher($container);

        $result = $dispatcher->dispatch(
            request: $request,
            middlewares: [TestContainerMiddleware::class],
            handler: static fn (Request $request): string => 'handler:' . $request->getMethod(),
        );

        self::assertSame('class-string:handler:GET', $result);
    }

    public function testDispatchSupportsShortCircuitMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $dispatcher = new MiddlewareDispatcher($container);
        $request = Request::create('/', 'GET');
        $nextWasCalled = false;

        $shortCircuitMiddleware = static function (Request $request, callable $next): string {
            return 'blocked';
        };

        $result = $dispatcher->dispatch(
            request: $request,
            middlewares: [$shortCircuitMiddleware],
            handler: static function (Request $request) use (&$nextWasCalled): string {
                $nextWasCalled = true;
                return 'handler:' . $request->getMethod();
            },
        );

        self::assertSame('blocked', $result);
        self::assertFalse($nextWasCalled);
    }

    public function testDispatchThrowsForInvalidMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $dispatcher = new MiddlewareDispatcher($container);
        $request = Request::create('/', 'GET');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not resolve middleware');

        $dispatcher->dispatch(
            request: $request,
            middlewares: [new \stdClass()],
            handler: static fn (Request $request): string => 'handler:' . $request->getMethod(),
        );
    }
}

final class TestContainerMiddleware
{
}
