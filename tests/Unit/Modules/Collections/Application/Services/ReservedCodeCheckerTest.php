<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Application\Services\ReservedCodeChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class ReservedCodeCheckerTest extends TestCase
{
    private function checker(): ReservedCodeChecker
    {
        $routes = new RouteCollection();
        $routes->add('home', new Route('/'));
        $routes->add('news_section', new Route('/news/{category}'));
        $routes->add('forum', new Route('/forum'));
        $routes->add('collections_public', new Route('/{route}'));

        return new ReservedCodeChecker($routes);
    }

    public function testReservesLiteralFirstSegmentsOfRoutes(): void
    {
        self::assertTrue($this->checker()->isReserved('news'));
        self::assertTrue($this->checker()->isReserved('forum'));
    }

    public function testReservesFilesystemPaths(): void
    {
        self::assertTrue($this->checker()->isReserved('admin'));
        self::assertTrue($this->checker()->isReserved('assets'));
        self::assertTrue($this->checker()->isReserved('upload'));
    }

    public function testAllowsFreeCode(): void
    {
        self::assertFalse($this->checker()->isReserved('blog'));
        self::assertFalse($this->checker()->isReserved('catalog'));
    }

    public function testIgnoresPlaceholderFirstSegment(): void
    {
        // The catch-all `/{route}` must not reserve the literal "route".
        self::assertFalse($this->checker()->isReserved('route'));
    }
}
