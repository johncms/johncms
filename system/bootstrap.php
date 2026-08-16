<?php

declare(strict_types=1);

use Johncms\Security\BanIP;
use Johncms\Http\Environment;
use Johncms\System\i18n\Translator;

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

// If there are no dependencies, we stop the script and displays an error
if (! is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    die('<h1>ERROR</h1><p>Missing dependencies</p>');
}

define('START_MEMORY', memory_get_usage());
define('START_TIME', microtime(true));

require dirname(__DIR__) . '/vendor/autoload.php';

defined('_IN_JOHNCMS') || die('Error: restricted access');

// Load the configuration
$config = (new \Johncms\Config\ConfigLoader(CONFIG_PATH . 'autoload'))->load();
\Johncms\Config\ConfigRepository::init($config);

// Error handling. Whatever PHP reports on its own — above all the failures of the boot itself,
// which happen before the handlers below exist — goes to the file of the application logger, so
// there is a single log to read.
error_reporting(DEBUG ? E_ALL : E_ALL & ~E_DEPRECATED);
ini_set('display_errors', DEBUG ? 'On' : 'Off');
ini_set('log_errors', 'On');
ini_set('error_log', \Johncms\Logs\LoggerFactory::currentFile());

$container = \Johncms\Container\PSRContainerFactory::getContainer();

// Registered here rather than in the front controller: everything below — sessions, the ban
// check, the translator, module autoloading — used to run before any handler existed, so a
// failure during boot printed an uncaught fatal and was never logged.
(new \Johncms\Logs\GlobalErrorHandler(
    logger:    $container->get(\Psr\Log\LoggerInterface::class),
    container: $container
))->registerHandlers();

// Services that outlive a single request read the current one off the stack. This is the bottom
// entry, covering everything resolved during boot and the console commands; the kernel pushes the
// request of each cycle on top of it. The front controller takes this request as the return value
// of the bootstrap and hands it to the kernel.
$bootRequest = $container->get(\Johncms\Http\RequestFactory::class)($container);
$container->get(\Symfony\Component\HttpFoundation\RequestStack::class)->push($bootRequest);

if (! defined('CONSOLE_MODE') || CONSOLE_MODE === false) {
    header('X-Powered-CMS: JohnCMS');
    header('X-CMS-Version: ' . CMS_VERSION);

    // The session is started explicitly and before anything else touches the facade: reading a
    // key would otherwise start it implicitly (the translator below looks up the 'lng' key), and
    // the point where a session opens must not depend on resolution order. The session name is
    // configured by SessionFactory. In a worker runtime this boot runs once — Kernel::handle()
    // starts the session of every subsequent request.
    $container->get(Johncms\Http\Session::class)->start();

    /** @var PDO $db */
    $db = $container->get(PDO::class);

    (new BanIP())->checkBan();

    // System cleanup
    new Johncms\System\Utility\Cleanup($db);
}

// The visitor is not identified here. Kernel::handle() does it for every request, including the
// first, and it must be the only place: the kernel clears the per-request state of the shared
// services at the start of each cycle, so anything decided during boot — the reissued sign-in
// cookie among it — would be thrown away moments later.

// Register the system languages domain and folder
$translator = di(Translator::class);
$translator->addTranslationDomain('system', __DIR__ . '/locale');
$translator->defaultDomain('system');
// Register language helpers
Gettext\TranslatorFunctions::register($translator);

// The request of this process, for the front controller: require returns it.
return $bootRequest;
