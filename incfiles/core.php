<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

define('ROOTPATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
require ROOTPATH . 'system/bootstrap.php';

////////////////////////////////////////////////////////////////////////////////
// Автозагрузка Классов                                                       //
////////////////////////////////////////////////////////////////////////////////
spl_autoload_register('autoload');
function autoload($name)
{
    $file = ROOTPATH . 'incfiles/classes/' . $name . '.php';
    if (file_exists($file)) {
        require_once($file);
    }
}

////////////////////////////////////////////////////////////////////////////////
// Инициализируем заглушку старого ядра системы                               //
////////////////////////////////////////////////////////////////////////////////
new core;

////////////////////////////////////////////////////////////////////////////////
// Получаем переменные для совместимости со старыми модулями                  //
////////////////////////////////////////////////////////////////////////////////

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();

$set = $container->get('config')['johncms']; // Системные настройки

$lng = core::$lng;                           // Фразы языка                        //TODO: переделать

$user_id = core::$user_id;                   // Идентификатор пользователя         //TODO: переделать
$rights = core::$user_rights;                // Права доступа                      //TODO: переделать
$datauser = core::$user_data;                // Все данные пользователя            //TODO: переделать
$set_user = core::$user_set;                 // Пользовательские настройки         //TODO: переделать
$ban = core::$user_ban;                      // Бан                                //TODO: переделать
$login = isset($datauser['name']) ? $datauser['name'] : false;                          //TODO: переделать
$kmess = $set_user['kmess'] > 4 && $set_user['kmess'] < 100 ? $set_user['kmess'] : 10;  //TODO: переделать

$page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? intval($_REQUEST['page']) : 1;
$start = isset($_REQUEST['page']) ? $page * $kmess - $kmess : (isset($_GET['start']) ? abs(intval($_GET['start'])) : 0);

// Закрытие сайта / редирект гостей на страницу ожидания
$headmod = isset($headmod) ? $headmod : '';

if (($set['site_access'] == 0 || $set['site_access'] == 1) && $headmod != 'login' && !core::$user_id) {
    header('Location: ' . $set['homeurl'] . '/closed.php');
}
