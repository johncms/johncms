<?php
/*
 * mobiCMS Content Management System (http://mobicms.net)
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

// Проверяем версию PHP
if (version_compare(PHP_VERSION, '7', '>=')) {
    //TODO: после полного перевода на новое ядро, раскоментировать
    //die('<div style="text-align: center; font-size: xx-large"><strong>ERROR!</strong><br>Compatibility Pack can not work with PHP 7</div>');
}

// Корневая папка
define('ROOTPATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

// Подключаем новую систему
require ROOTPATH . 'system/bootstrap.php';

/*
-----------------------------------------------------------------
Автозагрузка Классов
-----------------------------------------------------------------
*/
spl_autoload_register('autoload');
function autoload($name)
{
    $file = ROOTPATH . 'incfiles/classes/' . $name . '.php';
    if (file_exists($file)) {
        require_once($file);
    }
}

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
$rootpath = ROOTPATH;
$ip = core::$ip; // Адрес IP
$agn = core::$user_agent; // User Agent
$set = core::$system_set; // Системные настройки
$lng = core::$lng; // Фразы языка
$is_mobile = core::$is_mobile; // Определение мобильного браузера
$home = $set['homeurl']; // Домашняя страница

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

/*
-----------------------------------------------------------------
Получаем и фильтруем основные переменные для системы
-----------------------------------------------------------------
*/
//$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : false; //TODO: после отвязки раскомментировать
//$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : ''; //TODO: после отвязки раскомментировать
//$mod = isset($_REQUEST['mod']) ? trim($_REQUEST['mod']) : ''; //TODO: после отвязки раскомментировать
$user = isset($_REQUEST['user']) ? abs(intval($_REQUEST['user'])) : false;
//$do = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : false;
$page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? intval($_REQUEST['page']) : 1;
$start = isset($_REQUEST['page']) ? $page * $kmess - $kmess : (isset($_GET['start']) ? abs(intval($_GET['start'])) : 0);
$headmod = isset($headmod) ? $headmod : '';

/*
-----------------------------------------------------------------
Закрытие сайта / редирект гостей на страницу ожидания
-----------------------------------------------------------------
*/
if ((core::$system_set['site_access'] == 0 || core::$system_set['site_access'] == 1) && $headmod != 'login' && !core::$user_id) {
    header('Location: ' . core::$system_set['homeurl'] . '/closed.php');
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