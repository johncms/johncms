<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\UrlMatcherFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class UrlMatcherFactoryTest extends TestCase
{
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = CACHE_PATH . UrlMatcherFactory::CACHE_FILE;
        @unlink($this->cacheFile);
    }

    protected function tearDown(): void
    {
        @unlink($this->cacheFile);
    }

    public function testWithoutTheCacheTheCollectionIsMatchedDirectly(): void
    {
        $matcher = $this->factory()->create($this->routes(...), new RequestContext(), useCache: false);

        self::assertInstanceOf(UrlMatcher::class, $matcher);
        self::assertSame('forum_handler', $matcher->match('/forum')['_handler']);
        self::assertFileDoesNotExist($this->cacheFile);
    }

    public function testTheFirstRequestDumpsTheRoutesAndTheNextOneDoesNotBuildThem(): void
    {
        $matcher = $this->factory()->create($this->routes(...), new RequestContext(), useCache: true);

        self::assertInstanceOf(CompiledUrlMatcher::class, $matcher);
        self::assertFileExists($this->cacheFile);
        self::assertSame('forum_handler', $matcher->match('/forum')['_handler']);

        // The collection is not built a second time: with the dump in place, the twenty-odd
        // route files are never read again.
        $cached = $this->factory()->create(
            static fn (): RouteCollection => self::fail('The routes were built even though the dump was there.'),
            new RequestContext(),
            useCache: true
        );

        self::assertSame('forum_handler', $cached->match('/forum')['_handler']);
    }

    /**
     * The attributes a route carries — the module it belongs to, the permission it asks for —
     * have to survive the dump; they are what the pipeline reads off the match.
     */
    public function testTheAttributesOfARouteSurviveTheDump(): void
    {
        $matcher = $this->factory()->create($this->routes(...), new RequestContext(), useCache: true);

        $match = $matcher->match('/admin/news');

        self::assertSame('news.manage', $match['_permission']);
        self::assertSame('news', $match['_module']);
    }

    private function factory(): UrlMatcherFactory
    {
        return new UrlMatcherFactory(new NullLogger());
    }

    private function routes(): RouteCollection
    {
        $routes = new RouteCollection();
        $routes->add('forum', new Route('/forum', ['_handler' => 'forum_handler'], [], [], '', [], ['GET']));
        $routes->add(
            'news.admin',
            new Route(
                '/admin/news',
                ['_handler' => 'news_handler', '_permission' => 'news.manage', '_module' => 'news'],
                [],
                [],
                '',
                [],
                ['GET']
            )
        );

        return $routes;
    }
}
