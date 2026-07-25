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
$container->set(
    \Johncms\Http\Request::class,
    $container->get(\Johncms\Http\RequestFactory::class)($container)
);

if (! defined('CONSOLE_MODE') || CONSOLE_MODE === false) {
    header('X-Powered-CMS: JohnCMS');
    header('X-CMS-Version: ' . CMS_VERSION);

    session_name('SESID');
    session_start();

    /** @var Environment $env */
    $env = $container->get(Environment::class);

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

/** @var Johncms\System\Users\UserConfig $userConfig */
$userConfig = $container->get(User::class)->config;

$page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? (int) ($_REQUEST['page']) : 1;
$start = isset($_REQUEST['page']) ? $page * $userConfig->kmess - $userConfig->kmess : (isset($_GET['start']) ? abs((int) ($_GET['start'])) : 0);
