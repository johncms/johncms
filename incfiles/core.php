<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

define('ROOTPATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
require ROOTPATH . 'system/bootstrap.php';

// Автозагрузка Классов
spl_autoload_register('autoload');
function autoload($name)
{
    $file = ROOT_PATH . 'incfiles/classes/' . $name . '.php';
    if (file_exists($file)) {
        require_once($file);
    }
}

// Инициализируем заглушку старого ядра системы
new core;

$kmess = 10;  //TODO: переделать
$page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? intval($_REQUEST['page']) : 1;
$start = isset($_REQUEST['page']) ? $page * $kmess - $kmess : (isset($_GET['start']) ? abs(intval($_GET['start'])) : 0);
