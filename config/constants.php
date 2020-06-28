<?php

const DS = DIRECTORY_SEPARATOR;

define('ROOT_PATH', dirname(__DIR__) . DS);
const ASSETS_PATH = ROOT_PATH . 'assets' . DS;
const CONFIG_PATH = ROOT_PATH . 'config' . DS;
const DATA_PATH = ROOT_PATH . 'data' . DS;
const UPLOAD_PATH = ROOT_PATH . 'upload' . DS;
const CACHE_PATH = DATA_PATH . 'cache' . DS;
const LOG_PATH = DATA_PATH . 'logs' . DS;
const THEMES_PATH = ROOT_PATH . 'themes' . DS;
const CMS_VERSION = '9.3.0';

const USE_CRON = false;

// Включаем режим отладки
const DEBUG = true;

// Константа для проверки подключенного ядра
const _IN_JOHNCMS = true;

// Включение строгого режима для БД
const DB_STRICT_MODE = true;

// Включаем режим отладки для всех пользователей (в т.ч. для гостей)
// Использовать только когда вы понимаете что это вам действительно нужно т.к. отладочная информация может содержать конфиденциальные данные!
const DEBUG_FOR_ALL = false;
