<?php

declare(strict_types=1);

namespace Tests\Functional;

use Johncms\Container\PSRContainerFactory;
use Johncms\Http\Kernel;
use Johncms\Http\Request;
use Johncms\Http\Session;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Harness for the functional suite: boots the application once and drives real
 * requests through Kernel::handle().
 *
 * Two things to know before adding tests here:
 *
 * 1. It runs against the database of the local stand (config/autoload/database.local.php).
 *    Without a reachable database every test is skipped, which is why CI — which runs
 *    `composer test:unit` — stays green without one. Assert on statuses and structural
 *    fragments, never on the content of a particular install.
 * 2. Several requests may be driven through one process, including several through the same
 *    controller: nothing holds a request beyond the cycle it belongs to, and the kernel resets the
 *    shared services that cache per-request state. RequestIsolationTest is what guards that.
 *    Render is the exception — the page title is still process state, so do not assert on it
 *    across requests.
 */
abstract class FunctionalTestCase extends TestCase
{
    private static bool $booted = false;

    private static ?string $bootFailure = null;

    protected function setUp(): void
    {
        $this->bootApplication();

        // The container is booted once per process, so the session facade is shared by every test
        // in the class. Under CONSOLE_MODE it holds in-memory storage (SessionFactory), and
        // clearing it keeps one test from seeing what another one wrote.
        $this->container()->get(Session::class)->clear();
    }

    protected function handleRequest(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $server = [],
        array $cookies = []
    ): Response {
        $request = Request::create($uri, $method, $parameters, $cookies, [], $server);

        // Legacy code still reads the superglobals directly — checkRedirect() through
        // pageNotFound() and UserStat. Keep them in sync with the request under test until all of
        // it goes through Request.
        $_SERVER['REQUEST_URI'] = $request->getRequestUri();
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_GET = $request->query->all();
        $_POST = $request->request->all();
        $_COOKIE = $request->cookies->all();

        return $this->container()->get(Kernel::class)->handle($request);
    }

    protected function container(): ContainerInterface
    {
        return PSRContainerFactory::getContainer();
    }

    private function bootApplication(): void
    {
        if (self::$booted) {
            if (self::$bootFailure !== null) {
                self::markTestSkipped(self::$bootFailure);
            }

            return;
        }

        self::$booted = true;

        if (! is_file(CONFIG_PATH . 'autoload' . DS . 'database.local.php')) {
            self::$bootFailure = 'The application is not installed: config/autoload/database.local.php is missing.';
            self::markTestSkipped(self::$bootFailure);
        }

        // Skips the web-only part of the bootstrap: sessions, headers, the ban check, the
        // cleanup job and the output buffer. Everything the kernel needs — config, container,
        // translations, module autoloading — is set up either way.
        define('CONSOLE_MODE', true);
        require ROOT_PATH . 'system' . DS . 'bootstrap.php';

        // The bootstrap registers GlobalErrorHandler; PHPUnit must keep its own handlers, and the
        // kernel maps failures to responses on its own, which is what these tests assert on.
        restore_error_handler();
        restore_exception_handler();

        try {
            $this->container()->get(PDO::class)->query('SELECT 1');
        } catch (PDOException $exception) {
            self::$bootFailure = 'The database of the local stand is not reachable: ' . $exception->getMessage();
            self::markTestSkipped(self::$bootFailure);
        }
    }
}
