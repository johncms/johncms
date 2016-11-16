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
