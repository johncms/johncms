<?php

// Guarded because tooling that boots the Composer autoloader more than once in a process
// (PHPStan's phar does) would otherwise re-run this file and warn about a redefined constant.
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

define('ROOT_PATH', dirname(__DIR__) . DS);

// Web document root. Everything below this path is directly reachable by URL.
const PUBLIC_PATH = ROOT_PATH . 'public' . DS;

const CONFIG_PATH = ROOT_PATH . 'config' . DS;
const DATA_PATH = ROOT_PATH . 'data' . DS;
const CACHE_PATH = DATA_PATH . 'cache' . DS;
const LOG_PATH = DATA_PATH . 'logs' . DS;
const MODULES_PATH = ROOT_PATH . 'modules' . DS;

// Theme sources and templates. Not web-accessible.
const THEMES_PATH = ROOT_PATH . 'themes' . DS;
// Published theme assets. Web-accessible.
const PUBLIC_THEMES_PATH = PUBLIC_PATH . 'themes' . DS;

const ASSETS_PATH = PUBLIC_PATH . 'assets' . DS;
const UPLOAD_PATH = PUBLIC_PATH . 'upload' . DS;
const CMS_VERSION = '9.9';

// Cache container. Recommended for production mode.
// Remove the data/cache/container.php file to clear the cache. It creates automatically.
const CACHE_CONTAINER = false;

// Cache the routes. Recommended for production mode: the route files are then read once instead
// of on every request. Remove the data/cache/routes.php file after changing a route.
const CACHE_ROUTES = false;

// Safe mode for modules: only the ones shipped with the CMS are loaded, third-party modules are
// left out. The way back into a site that an installed module takes down — switch it on, remove
// the module, switch it back off.
const MODULES_SAFE_MODE = false;

// Включаем режим отладки
const DEBUG = true;

// Константа для проверки подключенного ядра
const _IN_JOHNCMS = true;

// Включение строгого режима для БД
const DB_STRICT_MODE = true;

// Включаем режим отладки для всех пользователей (в т.ч. для гостей)
// Использовать только когда вы понимаете что это вам действительно нужно т.к. отладочная информация может содержать конфиденциальные данные!
const DEBUG_FOR_ALL = false;
