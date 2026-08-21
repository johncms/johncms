<?php

declare(strict_types=1);

namespace Tests\Functional;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authorization\RoleSeeder;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Config\ConfigRepository;
use Johncms\Container\PSRContainerFactory;
use Johncms\Http\Kernel;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Security\ClientInfoDTO;
use Johncms\Users\User;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\RunsMigrations;

/**
 * Harness for the functional suite: boots the application once and drives real requests through
 * Kernel::handle().
 *
 * Three things to know before adding tests here:
 *
 * 1. The database is SQLite in memory, built by the migrations of the core and of every module,
 *    with the system roles seeded into it. It belongs to the process and nothing else reads it,
 *    so a test states the data it needs — FunctionalUserFactory writes the accounts — instead of
 *    looking for what an installation happens to have. No stand and no server are involved, which
 *    is why the suite runs in CI.
 * 2. Every test runs inside a transaction that is rolled back afterwards, so whatever a test
 *    writes is gone by the next one. Nothing has to be cleaned up by hand.
 * 3. Several requests may be driven through one process, including several through the same
 *    controller: nothing holds a request beyond the cycle it belongs to, and the kernel resets the
 *    shared services that cache per-request state. RequestIsolationTest is what guards that.
 *    Render is the exception — the page title is still process state, so do not assert on it
 *    across requests.
 */
abstract class FunctionalTestCase extends TestCase
{
    use RunsMigrations;

    private static bool $booted = false;

    protected function setUp(): void
    {
        $this->bootApplication();

        Capsule::connection()->beginTransaction();

        // The container is booted once per process, so the session facade is shared by every test
        // in the class. Under CONSOLE_MODE it holds in-memory storage (SessionFactory), and
        // clearing it keeps one test from seeing what another one wrote.
        $this->container()->get(Session::class)->clear();
    }

    protected function tearDown(): void
    {
        Capsule::connection()->rollBack();

        parent::tearDown();
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

    /**
     * Signs a test in as the given user and returns the cookies to drive requests with.
     *
     * Opens a real session and lets the request go through the real authenticator, so what the
     * test exercises is the path live visitors take — no test-only seam has to exist in
     * production code for this.
     *
     * @return array<string, string> Cookies for handleRequest().
     */
    protected function actingAs(User|int $user, bool $remember = true): array
    {
        $sessions = $this->container()->get(AuthSessionManager::class);
        $issued = $sessions->start(
            $user instanceof User ? $user->id : $user,
            $remember,
            new ClientInfoDTO('127.0.0.1', '', 'phpunit')
        );

        return [$sessions->settings()->cookieName => $issued->token];
    }

    protected function container(): ContainerInterface
    {
        return PSRContainerFactory::getContainer();
    }

    private function bootApplication(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        // Skips the web-only part of the bootstrap: sessions, headers, the ban check, the
        // cleanup job and the output buffer. Everything the kernel needs — config, container,
        // translations, module autoloading — is set up either way.
        define('CONSOLE_MODE', true);
        require ROOT_PATH . 'system' . DS . 'bootstrap.php';

        // The bootstrap registers GlobalErrorHandler; PHPUnit must keep its own handlers, and the
        // kernel maps failures to responses on its own, which is what these tests assert on.
        restore_error_handler();
        restore_exception_handler();

        $this->useInMemoryDatabase();
        $this->migrateEverything();
        $this->container()->get(RoleSeeder::class)->seed();
    }

    /**
     * Points the application at SQLite before anything asks for the connection.
     *
     * Nothing has read the configuration of the database yet: under CONSOLE_MODE the bootstrap
     * builds no PDO, and PdoFactory reads pdo.db_driver when the container is first asked for
     * one — which happens here, so that the connection every later service gets is this one.
     */
    private function useInMemoryDatabase(): void
    {
        ConfigRepository::init(
            array_replace(
                ConfigRepository::all(),
                ['pdo' => ['db_driver' => 'sqlite', 'db_name' => ':memory:']]
            )
        );

        $this->container()->get(PDO::class);
    }
}
