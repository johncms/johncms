<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\RouteMatchResult;
use Johncms\Router\SymfonyRouteMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class SymfonyRouteMatcherTest extends TestCase
{
    public function testDispatchReturnsFoundResultWithHandlerParamsAndMiddlewares(): void
    {
        $routes = new RouteCollection();
        $routes->add(
            'forum.topic',
            new Route(
                '/forum/topic/{id}',
                [
                    '_handler' => 'topic_handler',
                    '_middlewares' => ['auth', 'forum_access'],
                ],
                [],
                [],
                '',
                [],
                ['GET'],
            ),
        );

        $context = new RequestContext();
        $matcher = new SymfonyRouteMatcher(new UrlMatcher($routes, $context), $context);

        $result = $matcher->dispatch('GET', '/forum/topic/42');

        self::assertSame(RouteMatchResult::FOUND, $result->status);
        self::assertSame('topic_handler', $result->handler);
        self::assertSame(['id' => '42'], $result->params);
        self::assertSame(['auth', 'forum_access'], $result->middlewares);
    }

    public function testDispatchReturnsMethodNotAllowedResult(): void
    {
        $routes = new RouteCollection();
        $routes->add('guestbook.clean', new Route('/guestbook/clean', ['_handler' => 'clean_handler'], [], [], '', [], ['GET']));

        $context = new RequestContext();
        $matcher = new SymfonyRouteMatcher(new UrlMatcher($routes, $context), $context);

        $result = $matcher->dispatch('POST', '/guestbook/clean');

        self::assertSame(RouteMatchResult::METHOD_NOT_ALLOWED, $result->status);
        self::assertContains('GET', $result->allowedMethods);
    }

    public function testDispatchReturnsNotFoundResult(): void
    {
        $routes = new RouteCollection();
        $routes->add('home', new Route('/', ['_handler' => 'home_handler'], [], [], '', [], ['GET']));

        $context = new RequestContext();
        $matcher = new SymfonyRouteMatcher(new UrlMatcher($routes, $context), $context);

        $result = $matcher->dispatch('GET', '/unknown');

        self::assertSame(RouteMatchResult::NOT_FOUND, $result->status);
        self::assertNull($result->handler);
        self::assertSame([], $result->params);
        self::assertSame([], $result->middlewares);
    }
}
