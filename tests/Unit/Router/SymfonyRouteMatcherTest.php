<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Http\Request;
use Johncms\Http\RequestPathNormalizer;
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
        $matcher = new SymfonyRouteMatcher(new UrlMatcher($routes, $context), $context, new RequestPathNormalizer());

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
        $matcher = new SymfonyRouteMatcher(new UrlMatcher($routes, $context), $context, new RequestPathNormalizer());

        $result = $matcher->dispatch('POST', '/guestbook/clean');

        self::assertSame(RouteMatchResult::METHOD_NOT_ALLOWED, $result->status);
        self::assertContains('GET', $result->allowedMethods);
    }

    public function testHigherPriorityRouteIsMatchedFirstForSamePath(): void
    {
        $routes = new RouteCollection();
        $routes->add('homepage.index', new Route('/', ['_handler' => 'default_handler'], [], [], '', [], ['GET']), 0);
        $routes->add('custom.homepage', new Route('/', ['_handler' => 'override_handler'], [], [], '', [], ['GET']), 1);

        $context = new RequestContext();
        $matcher = new SymfonyRouteMatcher(new UrlMatcher($routes, $context), $context, new RequestPathNormalizer());

        $result = $matcher->dispatch('GET', '/');

        self::assertSame(RouteMatchResult::FOUND, $result->status);
        self::assertSame('override_handler', $result->handler);
    }

    public function testMatchRequestNormalizesThePathAndRefreshesTheContext(): void
    {
        $routes = new RouteCollection();
        $routes->add('forum.index', new Route('/forum', ['_handler' => 'forum_handler'], [], [], '', [], ['GET']));

        $context = new RequestContext();
        $matcher = new SymfonyRouteMatcher(new UrlMatcher($routes, $context), $context, new RequestPathNormalizer());

        // Trailing slash and the legacy entry point both have to reach the same route.
        self::assertSame(RouteMatchResult::FOUND, $matcher->matchRequest(Request::create('/forum/'))->status);
        self::assertSame(RouteMatchResult::FOUND, $matcher->matchRequest(Request::create('/forum/index.php'))->status);

        $matcher->matchRequest(Request::create('https://example.org/forum', 'GET'));

        self::assertSame('example.org', $context->getHost());
        self::assertSame('https', $context->getScheme());
    }

    public function testDispatchReturnsNotFoundResult(): void
    {
        $routes = new RouteCollection();
        $routes->add('home', new Route('/', ['_handler' => 'home_handler'], [], [], '', [], ['GET']));

        $context = new RequestContext();
        $matcher = new SymfonyRouteMatcher(new UrlMatcher($routes, $context), $context, new RequestPathNormalizer());

        $result = $matcher->dispatch('GET', '/unknown');

        self::assertSame(RouteMatchResult::NOT_FOUND, $result->status);
        self::assertNull($result->handler);
        self::assertSame([], $result->params);
        self::assertSame([], $result->middlewares);
    }
}
