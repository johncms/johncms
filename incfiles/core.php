<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');
//Error_Reporting(E_ALL & ~E_NOTICE);
ini_set('session.use_trans_sid', '0');
ini_set('arg_separator.output', '&amp;');
// ini_set('display_errors', 'Off');
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

// Корневая папка
define('DS', DIRECTORY_SEPARATOR);
define('ROOTPATH', dirname(dirname(__FILE__)) . DS);

/*
-----------------------------------------------------------------
Автозагрузка Классов
-----------------------------------------------------------------
*/
spl_autoload_register('autoload');
function autoload($name)
{
    $file = ROOTPATH . 'incfiles' . DS . 'classes' . DS . $name . '.php';
    if (file_exists($file)) {
        require_once($file);
    }
}

require_once(ROOTPATH . 'incfiles' . DS . 'functions.php');

/*
-----------------------------------------------------------------
Инициализируем Ядро системы
-----------------------------------------------------------------
*/
new core;

/*
-----------------------------------------------------------------
Получаем системные переменные для совместимости со старыми модулями
-----------------------------------------------------------------
*/
$ip = core::$ip; // Адрес IP
$agn = core::$user_agent; // User Agent
$set = core::$system_set; // Системные настройки
$lng = core::$lng; // Фразы языка
$is_mobile = core::$is_mobile; // Определение мобильного браузера
$home = $set['homeurl']; // Домашняя страница
$db = core::$db;

/*
-----------------------------------------------------------------
Получаем пользовательские переменные
-----------------------------------------------------------------
*/
$user_id = core::$user_id; // Идентификатор пользователя
$rights = core::$user_rights; // Права доступа
$datauser = core::$user_data; // Все данные пользователя
$set_user = core::$user_set; // Пользовательские настройки
$ban = core::$user_ban; // Бан
$login = isset($datauser['name']) ? $datauser['name'] : false;
$kmess = $set_user['kmess'] > 4 && $set_user['kmess'] < 100 ? $set_user['kmess'] : 10;

function validate_referer()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (@!empty($_SERVER['HTTP_REFERER'])) {
        $ref = parse_url(@$_SERVER['HTTP_REFERER']);
        if ($_SERVER['HTTP_HOST'] === $ref['host']) return;
    }
    die('Invalid request');
}

if ($rights) {
    validate_referer();
}

/*
-----------------------------------------------------------------
Получаем и фильтруем основные переменные для системы
-----------------------------------------------------------------
*/
$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : false;
$user = isset($_REQUEST['user']) ? abs(intval($_REQUEST['user'])) : false;
$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : '';
$mod = isset($_REQUEST['mod']) ? trim($_REQUEST['mod']) : '';
$do = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : false;
$page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? intval($_REQUEST['page']) : 1;
$start = isset($_REQUEST['page']) ? $page * $kmess - $kmess : (isset($_GET['start']) ? abs(intval($_GET['start'])) : 0);
$headmod = isset($headmod) ? $headmod : '';

/*
-----------------------------------------------------------------
Закрытие сайта / редирект гостей на страницу ожидания
-----------------------------------------------------------------
*/
if ((core::$system_set['site_access'] == 0 || core::$system_set['site_access'] == 1) && $headmod != 'login' && !core::$user_id) {
    header('Location: ' . core::$system_set['homeurl'] . '/closed.php'); exit;
}

/*
-----------------------------------------------------------------
Буфферизация вывода
-----------------------------------------------------------------
*/
if ($set['gzip'] && @extension_loaded('zlib')) {
    @ini_set('zlib.output_compression_level', 3);
    ob_start('ob_gzhandler');
} else {
    ob_start();
}