<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\MiddlewareDispatcher;
use Johncms\Router\MiddlewareInterface;
use Johncms\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testDispatchRunsMiddlewaresInExpectedOrder(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $dispatcher = new MiddlewareDispatcher($container);
        $request = Request::create('/forum', 'GET');
        $executionLog = [];

        $first = static function (Request $request, callable $next) use (&$executionLog): Response {
            $executionLog[] = 'first.before';
            $result = $next($request);
            $executionLog[] = 'first.after';
            return $result;
        };

        $second = static function (Request $request, callable $next) use (&$executionLog): Response {
            $executionLog[] = 'second.before';
            $result = $next($request);
            $executionLog[] = 'second.after';
            return $result;
        };

        $result = $dispatcher->dispatch(
            request: $request,
            middlewares: [$first, $second],
            handler: static function (Request $request) use (&$executionLog): Response {
                $executionLog[] = 'handler';
                return new Response('ok:' . $request->getMethod());
            },
        );

        self::assertSame('ok:GET', $result->getContent());
        self::assertSame(
            ['first.before', 'second.before', 'handler', 'second.after', 'first.after'],
            $executionLog,
        );
    }

    public function testDispatchResolvesClassStringMiddlewareFromContainer(): void
    {
        $request = Request::create('/', 'GET');
        $middleware = new class () implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return new Response('class-string:' . $next($request)->getContent());
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
            handler: static fn (Request $request): Response => new Response('handler:' . $request->getMethod()),
        );

        self::assertSame('class-string:handler:GET', $result->getContent());
    }

    public function testDispatchSupportsShortCircuitMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $dispatcher = new MiddlewareDispatcher($container);
        $request = Request::create('/', 'GET');
        $nextWasCalled = false;

        $shortCircuitMiddleware = static function (Request $request, callable $next): Response {
            return new Response('blocked');
        };

        $result = $dispatcher->dispatch(
            request: $request,
            middlewares: [$shortCircuitMiddleware],
            handler: static function (Request $request) use (&$nextWasCalled): Response {
                $nextWasCalled = true;
                return new Response('handler:' . $request->getMethod());
            },
        );

        self::assertSame('blocked', $result->getContent());
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
            handler: static fn (Request $request): Response => new Response('handler:' . $request->getMethod()),
        );
    }
}

final class TestContainerMiddleware
{
}
