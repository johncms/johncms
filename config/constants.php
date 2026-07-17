<?php

const DS = DIRECTORY_SEPARATOR;

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

const USE_CRON = false;

// Cache container. Recommended for production mode.
// Remove the data/cache/container.php file to clear the cache. It creates automatically.
const CACHE_CONTAINER = false;

// Включаем режим отладки
const DEBUG = true;

// Константа для проверки подключенного ядра
const _IN_JOHNCMS = true;

// Включение строгого режима для БД
const DB_STRICT_MODE = true;

// Включаем режим отладки для всех пользователей (в т.ч. для гостей)
// Использовать только когда вы понимаете что это вам действительно нужно т.к. отладочная информация может содержать конфиденциальные данные!
const DEBUG_FOR_ALL = false;
