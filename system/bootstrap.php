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
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Config;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\Glob;

defined('_IN_JOHNCMS') || die('Error: restricted access');

error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

// Check the current PHP version
if (version_compare(PHP_VERSION, '7.2', '<')) {
    die('<div style="text-align: center; font-size: xx-large"><strong>ERROR!</strong><br>Your needs PHP 7.1 or higher</div>');
}

define('START_MEMORY', memory_get_usage());
define('START_TIME', microtime(true));
const DS = DIRECTORY_SEPARATOR;

define('ROOT_PATH', dirname(__DIR__) . DS);
const ASSETS_PATH = ROOT_PATH . 'assets' . DS;
const CONFIG_PATH = ROOT_PATH . 'config' . DS;
const DATA_PATH = ROOT_PATH . 'data' . DS;
const UPLOAD_PATH = ROOT_PATH . 'upload' . DS;
const CACHE_PATH = DATA_PATH . 'cache' . DS;

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
$env = App::getContainer()->get(EnvironmentInterface::class);

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

/**
 * Translate a message
 *
 * @param string $message
 * @param string $textDomain
 * @return string
 */
function _t($message, $textDomain = 'default')
{
    /** @var Translator $translator */
    static $translator;

    if (null === $translator) {
        $translator = App::getContainer()->get(Translator::class);
    }

    return $translator->translate($message, $textDomain);
}

/**
 * Translate a plural message
 *
 * @param string $singular
 * @param string $plural
 * @param int    $number
 * @param string $textDomain
 * @return string
 */
function _p($singular, $plural, $number, $textDomain = 'default')
{
    /** @var Translator $translator */
    static $translator;

    if (null === $translator) {
        $translator = App::getContainer()->get(Translator::class);
    }

    return $translator->translatePlural($singular, $plural, $number, $textDomain);
}

/** @var UserInterface $userConfig */
$userConfig = $container->get(UserInterface::class)->getConfig();

$kmess = $userConfig->kmess; //TODO: удалить $kmess ВЕЗДЕ, где используется!!!
$page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? (int) ($_REQUEST['page']) : 1;
$start = isset($_REQUEST['page']) ? $page * $kmess - $kmess : (isset($_GET['start']) ? abs((int) ($_GET['start'])) : 0);

if (extension_loaded('zlib') && ! ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
} else {
    ob_start();
}
