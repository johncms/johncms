<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Mail\EmailSender;
use Johncms\Security\Csrf;
use Johncms\System\Http\Environment;
use Johncms\System\i18n\Translator;
use Johncms\System\Users\User;
use Psr\Container\ContainerInterface;

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

// Check the current PHP version
if (version_compare(PHP_VERSION, '7.2', '<')) {
    die('<h1>ERROR!</h1><p>Your needs PHP 7.2 or higher</p>');
}

// If there are no dependencies, we stop the script and displays an error
if (! is_file(__DIR__ . '/vendor/autoload.php')) {
    die('<h1>ERROR</h1><p>Missing dependencies</p>');
}

define('START_MEMORY', memory_get_usage());
define('START_TIME', microtime(true));

require __DIR__ . '/vendor/autoload.php';

defined('_IN_JOHNCMS') || die('Error: restricted access');

// Error handling
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
    ini_set('log_errors', 'On');
    ini_set('error_log', LOG_PATH . 'errors-' . date('Y-m-d') . '.log');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 'Off');
    ini_set('log_errors', 'Off');
}

header('X-Powered-CMS: JohnCMS');
header('X-CMS-Version: ' . CMS_VERSION);

session_name('SESID');
session_start();

/** @var ContainerInterface $container */
$container = Johncms\System\Container\Factory::getContainer();

/** @var Environment $env */
$env = $container->get(Environment::class);

/** @var PDO $db */
$db = $container->get(PDO::class);

// Проверка на IP бан
$req = $db->query(
    "SELECT `ban_type`, `link` FROM `cms_ban_ip`
    WHERE '" . $env->getIp() . "' BETWEEN `ip1` AND `ip2`
    " . ($env->getIpViaProxy() ? " OR '" . $env->getIpViaProxy() . "' BETWEEN `ip1` AND `ip2`" : '') . '
    LIMIT 1'
);

if ($req->rowCount()) {
    $res = $req->fetch();

    switch ($res['ban_type']) {
        case 2:
            if (! empty($res['link'])) {
                header('Location: ' . $res['link']);
            } else {
                header('Location: http://johncms.com');
            }
            exit;
        case 3:
            //TODO: реализовать запрет регистрации
            //self::$deny_registration = true;
            break;
        default:
            header('HTTP/1.0 404 Not Found');
            exit;
    }
}

// System cleanup
new Johncms\System\Utility\Cleanup($db);

// Register the system languages domain and folder
$translator = di(Translator::class);
$translator->addTranslationDomain('system', __DIR__ . '/locale');
$translator->defaultDomain('system');
// Register language helpers
Gettext\TranslatorFunctions::register($translator);

/** @var Csrf $csrf */
$csrf = di(Csrf::class);

/** @var Johncms\System\View\Render $render */
$render = di(Johncms\System\View\Render::class);
$render->addData(['tools' => di(Johncms\System\Legacy\Tools::class), 'csrf_token' => $csrf->getToken()]);

/** @var Johncms\System\Users\UserConfig $userConfig */
$userConfig = $container->get(User::class)->config;

$page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? (int) ($_REQUEST['page']) : 1;
$start = isset($_REQUEST['page']) ? $page * $userConfig->kmess - $userConfig->kmess : (isset($_GET['start']) ? abs((int) ($_GET['start'])) : 0);

if (! defined('CONSOLE_MODE') || CONSOLE_MODE === false) {
    // If cron usage is disabled.
    if (! USE_CRON) {
        $cron_cache = CACHE_PATH . 'cron.cache';
        if (! file_exists($cron_cache) || filemtime($cron_cache) < (time() - 5)) {
            EmailSender::send();
            file_put_contents($cron_cache, time());
        }
    }

    if (extension_loaded('zlib') && ! ini_get('zlib.output_compression')) {
        ob_start('ob_gzhandler');
    } else {
        ob_start();
    }
}
