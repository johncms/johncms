<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\EnvironmentInterface;
use Johncms\Api\UserInterface;
use Psr\Container\ContainerInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Config;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\Glob;

defined('_IN_JOHNCMS') || die('Error: restricted access');

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
const DS = DIRECTORY_SEPARATOR;

define('ROOT_PATH', dirname(__DIR__) . DS);
const ASSETS_PATH = ROOT_PATH . 'assets' . DS;
const CONFIG_PATH = ROOT_PATH . 'config' . DS;
const DATA_PATH = ROOT_PATH . 'data' . DS;
const UPLOAD_PATH = ROOT_PATH . 'upload' . DS;
const UPLOAD_PUBLIC_PATH = '/upload/';
const CACHE_PATH = DATA_PATH . 'cache' . DS;
const LOG_PATH = DATA_PATH . 'logs' . DS;

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

require __DIR__ . '/vendor/autoload.php';

class App
{
    private static $container;

    /**
     * @return ServiceManager
     */
    public static function getContainer()
    {
        if (null === self::$container) {
            $config = [];

            // Read configuration
            foreach (Glob::glob(CONFIG_PATH . 'autoload/' . '{{,*.}global,{,*.}local}.php', Glob::GLOB_BRACE) as $file) {
                $config = ArrayUtils::merge($config, include $file);
            }

            $container = new ServiceManager;
            (new Config($config['dependencies']))->configureServiceManager($container);
            $container->setService('config', $config);
            self::$container = $container;
        }

        return self::$container;
    }
}

session_name('SESID');
session_start();

/** @var ContainerInterface $container */
$container = App::getContainer();

/** @var EnvironmentInterface $env */
$env = $container->get(EnvironmentInterface::class);

/** @var PDO $db */
$db = $container->get(PDO::class);

// Проверка на IP бан
$req = $db->query("
  SELECT `ban_type`, `link` FROM `cms_ban_ip`
  WHERE '" . $env->getIp() . "' BETWEEN `ip1` AND `ip2`
  " . ($env->getIpViaProxy() ? " OR '" . $env->getIpViaProxy() . "' BETWEEN `ip1` AND `ip2`" : '') . '
  LIMIT 1
');

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
            break;
        case 3:
            //TODO: реализовать запрет регистрации
            //self::$deny_registration = true;
            break;
        default:
            header('HTTP/1.0 404 Not Found');
            exit;
    }
}

// Автоочистка системы
$cacheFile = CACHE_PATH . 'system-cleanup.cache';

if (! file_exists($cacheFile) || filemtime($cacheFile) < (time() - 86400)) {
    new Johncms\Utility\Cleanup($db);
    file_put_contents($cacheFile, time());
}

/** @var UserInterface $userConfig */
$userConfig = $container->get(UserInterface::class)->config;

$page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? (int) ($_REQUEST['page']) : 1;
$start = isset($_REQUEST['page']) ? $page * $userConfig->kmess - $userConfig->kmess : (isset($_GET['start']) ? abs((int) ($_GET['start'])) : 0);

if (extension_loaded('zlib') && ! ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
} else {
    ob_start();
}

require 'helpers.php';
