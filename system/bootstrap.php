<?php

declare(strict_types=1);

use Johncms\Modules\Modules;
use Johncms\Security\BanIP;
use Johncms\Http\Environment;
use Johncms\System\i18n\Translator;
use Johncms\System\Users\User;

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

// Error handling
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
    ini_set('log_errors', 'On');
    ini_set('error_log', LOG_PATH . 'errors-' . date('Y-m-d') . '.log');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', 'Off');
    ini_set('log_errors', 'Off');
}

$container = \Johncms\Container\PSRContainerFactory::getContainer();

// Registered here rather than in the front controller: everything below — sessions, the ban
// check, the translator, module autoloading — used to run before any handler existed, so a
// failure during boot printed an uncaught fatal and was never logged.
(new \Johncms\Logs\GlobalErrorHandler(
    logger:    $container->get(\Psr\Log\LoggerInterface::class),
    container: $container
))->registerHandlers();

// The Request service is synthetic, so it has to be published before anything resolves it.
// The kernel republishes the request of every cycle it handles; this one covers the legacy code
// that reaches for Request during boot (Environment, BanIP) and the console commands.
$bootRequest = $container->get(\Johncms\Http\RequestFactory::class)($container);
$container->set(\Johncms\Http\Request::class, $bootRequest);
// Services that outlive a single request read the current one off the stack. This is the bottom
// entry, covering everything resolved during boot and the console commands; the kernel pushes the
// request of each cycle on top of it.
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

// Register the system languages domain and folder
$translator = di(Translator::class);
$translator->addTranslationDomain('system', __DIR__ . '/locale');
$translator->defaultDomain('system');
// Register language helpers
Gettext\TranslatorFunctions::register($translator);

(new Modules())->registerAutoloader();

// Resolving the current user authenticates the visitor by their cookies and, as a side effect,
// runs the ban check and records the IP history. Kept here so that happens on every request
// rather than on whichever service first asks for the user.
$container->get(User::class);
