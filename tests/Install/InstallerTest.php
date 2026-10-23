<?php

declare(strict_types=1);

namespace Tests\Install;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tests\Support\FunctionalDatabase;

/**
 * The installer, end to end: a site built from a fresh checkout through the pages a visitor fills
 * in, then opened by the administrator the installer created.
 *
 * The installer writes the configuration of the site into config/autoload and ends its steps with
 * exit, so it cannot run inside the process of PHPUnit, nor in a checkout that is a site already.
 * It runs under the built-in server of PHP instead, in a checkout without database.local.php and
 * system.local.php — the way CI has one — against the MySQL database TEST_DATABASE_URL names,
 * which it drops and creates anew. Elsewhere the test skips itself.
 */
final class InstallerTest extends TestCase
{
    private const string PASSWORD = 'installer-test-password';

    /** What the installer writes into config/autoload, and the test removes when it is done. */
    private const array WRITTEN = ['database.local.php', 'system.local.php', 'modules.local.php'];

    /** What makes a checkout a site, and the installer refuse to run. */
    private const array INSTALLED = ['database.local.php', 'system.local.php'];

    /** @var resource|null */
    private static $server = null;

    private static string $address = '';

    private static string $log = '';

    /** @var list<string> */
    private static array $existing = [];

    private HttpClientInterface $http;

    /** @var array<string, string> */
    private array $cookies = [];

    public static function setUpBeforeClass(): void
    {
        $database = FunctionalDatabase::fromEnvironment();

        if (! $database->isMysql()) {
            self::markTestSkipped(sprintf(
                'The installer sets a site up on MySQL only: point %s at a MySQL database.',
                FunctionalDatabase::VARIABLE
            ));
        }

        foreach (self::INSTALLED as $file) {
            if (is_file(CONFIG_PATH . 'autoload/' . $file)) {
                self::markTestSkipped(sprintf(
                    'This checkout is a site already (config/autoload/%s), which the installer refuses and the '
                    . 'test must not overwrite. Run it in a fresh checkout, the way CI does.',
                    $file
                ));
            }
        }

        self::$existing = array_values(
            array_filter(self::WRITTEN, static fn (string $file): bool => is_file(CONFIG_PATH . 'autoload/' . $file))
        );

        $database->prepare();
        self::startServer();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server !== null) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }

        foreach (array_diff(self::WRITTEN, self::$existing) as $file) {
            if (is_file(CONFIG_PATH . 'autoload/' . $file)) {
                unlink(CONFIG_PATH . 'autoload/' . $file);
            }
        }
    }

    protected function setUp(): void
    {
        $this->http = HttpClient::create(['base_uri' => 'http://' . self::$address, 'max_redirects' => 0]);
    }

    public function testASiteIsInstalledFromScratchAndOpensForItsAdministrator(): void
    {
        $database = FunctionalDatabase::fromEnvironment()->config();

        self::assertSame(200, $this->get('/install/')->getStatusCode(), 'The first step did not open.');
        self::assertSame(200, $this->get('/install/?step=2')->getStatusCode(), 'The check of the server did not open.');

        // The database: a wrong password and a database that is not there are refused with a word
        // about the field; the right details build the schema and lead on.
        $form = [
            'db_host'     => $database['db_host'],
            'db_port'     => $database['db_port'],
            'db_name'     => $database['db_name'],
            'db_user'     => $database['db_user'],
            'db_password' => $database['db_pass'],
        ];

        self::assertStringContainsString(
            'Invalid database user or password',
            $this->content($this->post('/install/?step=3', ['db_password' => $database['db_pass'] . '-wrong'] + $form))
        );
        self::assertStringContainsString(
            'Database does not exist',
            $this->content($this->post('/install/?step=3', ['db_name' => 'johncms_missing_test'] + $form))
        );
        self::assertFileDoesNotExist(CONFIG_PATH . 'autoload/database.local.php', 'A refused database was written down.');

        $this->assertRedirectsTo('/install/?step=4', $this->post('/install/?step=3', $form));
        self::assertFileExists(CONFIG_PATH . 'autoload/database.local.php');

        // The site and its administrator: a form left empty is refused, a filled one finishes the job.
        self::assertSame(
            200,
            $this->post('/install/?step=4', ['homeurl' => '', 'email' => '', 'admin_login' => '', 'admin_password' => ''])
                ->getStatusCode()
        );
        self::assertFileDoesNotExist(CONFIG_PATH . 'autoload/system.local.php', 'An empty form installed the site.');

        $this->assertRedirectsTo('/install/?step=5', $this->post('/install/?step=4', [
            'homeurl'        => 'http://' . self::$address,
            'email'          => 'admin@example.com',
            'admin_login'    => 'admin',
            'admin_password' => self::PASSWORD,
            'install_demo'   => 1,
        ]));
        self::assertFileExists(CONFIG_PATH . 'autoload/system.local.php');
        self::assertSame(200, $this->get('/install/?step=5')->getStatusCode(), 'The last step did not open.');

        // Once the site exists, the installer is closed: anybody could build it again otherwise.
        self::assertStringContainsString('The system is already installed', $this->content($this->get('/install/')));

        // The site, opened by the administrator the installer signed in.
        self::assertSame(200, $this->get('/')->getStatusCode(), 'The home page of the new site did not open.');
        self::assertSame(200, $this->get('/admin')->getStatusCode(), 'The panel did not let the new administrator in.');
    }

    private function get(string $uri): ResponseInterface
    {
        return $this->send('GET', $uri, []);
    }

    /**
     * @param array<string, string|int> $form
     */
    private function post(string $uri, array $form): ResponseInterface
    {
        return $this->send('POST', $uri, ['body' => $form]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function send(string $method, string $uri, array $options): ResponseInterface
    {
        $cookies = array_map(
            static fn (string $name, string $value): string => $name . '=' . $value,
            array_keys($this->cookies),
            $this->cookies
        );

        $response = $this->http->request($method, $uri, $options + [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (InstallerTest)',
                'Cookie'     => implode('; ', $cookies),
            ],
        ]);

        foreach ($response->getHeaders(false)['set-cookie'] ?? [] as $header) {
            [$name, $value] = explode('=', explode(';', $header, 2)[0], 2) + [1 => ''];
            $this->cookies[trim($name)] = $value;
        }

        return $response;
    }

    private function content(ResponseInterface $response): string
    {
        return $response->getContent(false);
    }

    private function assertRedirectsTo(string $location, ResponseInterface $response): void
    {
        self::assertSame(
            302,
            $response->getStatusCode(),
            sprintf('Expected a redirect to %s. The server log is in %s.', $location, self::$log)
        );
        self::assertSame($location, $response->getHeaders(false)['location'][0] ?? null);
    }

    private static function startServer(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');

        if ($socket === false) {
            throw new RuntimeException('No free port for the built-in server.');
        }

        self::$address = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        self::$log = sys_get_temp_dir() . '/johncms-installer-test.log';
        $server = proc_open(
            [PHP_BINARY, '-S', self::$address, '-t', ROOT_PATH . 'public', __DIR__ . '/router.php'],
            [0 => ['pipe', 'r'], 1 => ['file', self::$log, 'w'], 2 => ['file', self::$log, 'w']],
            $pipes,
            ROOT_PATH
        );

        if ($server === false) {
            throw new RuntimeException('The built-in server of PHP did not start.');
        }

        self::$server = $server;

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $connection = @fsockopen('127.0.0.1', (int) substr((string) strrchr(self::$address, ':'), 1));

            if ($connection !== false) {
                fclose($connection);

                return;
            }

            usleep(100_000);
        }

        throw new RuntimeException(sprintf('The built-in server did not answer at %s. Its log: %s', self::$address, self::$log));
    }
}
