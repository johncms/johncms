<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use Johncms\Router\Route;
use Johncms\Router\RouteCollectorFactory;
use Johncms\System\Users\User;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Guards the exemptions from the CSRF check.
 *
 * Every endpoint that skips the check is a hole reopened on purpose, and a list of them that
 * nobody reads grows silently. Pinning it here means adding one takes a change to this test,
 * which is what puts the decision in front of a reviewer.
 */
final class CsrfExemptionListTest extends TestCase
{
    /**
     * Route names allowed to carry Route::withoutCsrf().
     *
     * @var list<string>
     */
    private const EXEMPT_ROUTES = [];

    /**
     * Path patterns allowed in the "except" key of config/csrf.php.
     *
     * @var list<string>
     */
    private const EXEMPT_PATHS = [];

    public function testNoRouteIsExemptBeyondTheAllowedList(): void
    {
        $user = new User();
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(User::class)->willReturn($user);

        $exempt = [];

        foreach ((new RouteCollectorFactory())($container)->all() as $name => $route) {
            if ($route->getDefault(Route::CSRF_EXEMPT_ATTRIBUTE) === true) {
                $exempt[] = $name;
            }
        }

        sort($exempt);
        $allowed = self::EXEMPT_ROUTES;
        sort($allowed);

        self::assertSame($allowed, $exempt);
    }

    public function testNoPathIsExemptBeyondTheAllowedList(): void
    {
        $config = require CONFIG_PATH . 'csrf.php';

        self::assertIsArray($config);
        self::assertSame(self::EXEMPT_PATHS, $config['except'] ?? []);
    }
}
