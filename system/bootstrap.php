<?php
/*
 * JohnCMS Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

error_reporting(E_ALL & ~E_NOTICE);
//ini_set('display_errors', 'Off');
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

define('START_MEMORY', memory_get_usage());
define('START_TIME', microtime(true));
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR);

require __DIR__ . '/vendor/autoload.php';

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Config;
use Zend\Stdlib\ArrayObject as ConfigObject;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\Glob;

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
            foreach (Glob::glob(CONFIG_PATH . '{{,*.}global,{,*.}local}.php', Glob::GLOB_BRACE) as $file) {
                $config = ArrayUtils::merge($config, include $file);
            }

            $container = new ServiceManager;
            (new Config($config['dependencies']))->configureServiceManager($container);
            $container->setService('config', new ConfigObject($config, ConfigObject::ARRAY_AS_PROPS));
            self::$container = $container;
        }

        return self::$container;
    }
}

session_name('SESID');
session_start();

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Environment $env */
$env = App::getContainer()->get('env');

/** @var PDO $db */
$db = $container->get(PDO::class);

// Проверка на IP бан
$req = $db->query("
  SELECT `ban_type`, `link` FROM `cms_ban_ip`
  WHERE '" . $env->getIp() . "' BETWEEN `ip1` AND `ip2`
  " . ($env->getIpViaProxy() ? " OR '" . $env->getIpViaProxy() . "' BETWEEN `ip1` AND `ip2`" : '') . "
  LIMIT 1
");

if ($req->rowCount()) {
    $res = $req->fetch();

    switch ($res['ban_type']) {
        case 2:
            if (!empty($res['link'])) {
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
        default :
            header("HTTP/1.0 404 Not Found");
            exit;
    }
}

//TODO: Написать смену языка
$locale = 'ru';

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
//$translator->setCache(App::getContainer()->get(Zend\Cache\Storage\StorageInterface::class));
$translator->setLocale($locale);
unset($translator);

/**
 * Translate a message
 *
 * @param string $message
 * @param string $textDomain
 * @return string
 */
function _t($message, $textDomain = 'default')
{
    /** @var Zend\I18n\Translator\Translator $translator */
    static $translator;

    if (null === $translator) {
        $translator = App::getContainer()->get(Zend\I18n\Translator\Translator::class);
    }

    return $translator->translate($message, $textDomain);
}

/**
 * Translate a plural message
 *
 * @param string $singular
 * @param string $plural
 * @param int    $number
 * @return string
 */
function _p($singular, $plural, $number)
{
    /** @var Zend\I18n\Translator\Translator $translator */
    static $translator;

    if (null === $translator) {
        $translator = App::getContainer()->get(Zend\I18n\Translator\Translator::class);
    }

    return $translator->translatePlural($singular, $plural, $number);
}

if (extension_loaded('zlib')) {
    ob_start('ob_gzhandler');
} else {
    ob_start();
}
