<?php

declare(strict_types=1);

namespace Tests\Functional;

use Johncms\Container\PSRContainerFactory;
use Johncms\Http\Kernel;
use Johncms\Http\Request;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Harness for the functional suite (plan stage 3a): boots the application once and drives real
 * requests through Kernel::handle().
 *
 * Two things to know before adding tests here:
 *
 * 1. It runs against the database of the local stand (config/autoload/database.local.php).
 *    Without a reachable database every test is skipped, which is why CI — which runs
 *    `composer test:unit` — stays green without one. Assert on statuses and structural
 *    fragments, never on the content of a particular install.
 * 2. Every shared service that takes Request in its constructor keeps the first request of the
 *    process — 145 of the 221 controllers, plus collaborators such as PaginationFactory. So the
 *    contract of this suite is one request per route per process, and assertions stay on statuses
 *    and structure rather than on request-specific content. Stage 5 makes the request scope
 *    explicit and brings the isolation test for two sequential handle() calls.
 */
abstract class FunctionalTestCase extends TestCase
{
    private static bool $booted = false;

    private static ?string $bootFailure = null;

    protected function setUp(): void
    {
        $this->bootApplication();

        // The console bootstrap does not start a session, and legacy code reads $_SESSION
        // unconditionally. An array is enough: nothing here asserts on session persistence.
        $_SESSION = [];
    }

    protected function handleRequest(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $server = []
    ): Response {
        $request = Request::create($uri, $method, $parameters, [], [], $server);

        // Legacy code still reads the superglobals directly — checkRedirect() through
        // pageNotFound(), UserStat, the admin-theme detection of RenderEngineFactory. Keep them in
        // sync with the request under test until stage 5 routes all of it through Request.
        $_SERVER['REQUEST_URI'] = $request->getRequestUri();
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_GET = $request->query->all();
        $_POST = $request->request->all();

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
