<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var Johncms\System\Legacy\Tools $tools
 */

$config = di('config')['johncms'];

$title = __('Smilies');
$nav_chain->add($title);

$ext = ['gif', 'jpg', 'jpeg', 'png']; // Список разрешенных расширений
$smileys = [];

// Обрабатываем простые смайлы
foreach (glob(ASSETS_PATH . 'emoticons' . DS . 'simply' . DS . '*') as $var) {
    $file = basename($var);
    $name = explode('.', $file);
    if (in_array($name[1], $ext)) {
        $smileys['usr'][':' . $name[0]] = '<img src="' . $config['homeurl'] . '/assets/emoticons/simply/' . $file . '" alt="" />';
    }
}

// Обрабатываем Админские смайлы
foreach (glob(ASSETS_PATH . 'emoticons' . DS . 'admin' . DS . '*') as $var) {
    $file = basename($var);
    $name = explode('.', $file);
    if (in_array($name[1], $ext)) {
        $smileys['adm'][':' . $tools->trans($name[0]) . ':'] = '<img src="' . $config['homeurl'] . '/assets/emoticons/admin/' . $file . '" alt="" />';
        $smileys['adm'][':' . $name[0] . ':'] = '<img src="' . $config['homeurl'] . '/assets/emoticons/admin/' . $file . '" alt="" />';
    }
}

// Обрабатываем смайлы каталога
foreach (glob(ASSETS_PATH . 'emoticons' . DS . 'user' . DS . '*' . DS . '*') as $var) {
    $file = basename($var);
    $name = explode('.', $file);
    if (in_array($name[1], $ext)) {
        $path = $config['homeurl'] . '/assets/emoticons/user/' . basename(dirname($var));
        $smileys['usr'][':' . $tools->trans($name[0]) . ':'] = '<img src="' . $path . '/' . $file . '" alt="" />';
        $smileys['usr'][':' . $name[0] . ':'] = '<img src="' . $path . '/' . $file . '" alt="" />';
    }
}

$total = count($smileys['adm']) + count($smileys['usr']);

// Записываем в файл Кэша
if (file_put_contents(CACHE_PATH . 'smilies-list.cache', serialize($smileys))) {
    $message = __('Smilie cache updated successfully') . '<br>' . __('Total') . ':' . $total;
    echo $view->render(
        'system::pages/result',
        [
            'title'       => $title,
            'type'        => 'alert-success',
            'message'     => $message,
            'back_url'    => '/admin/',
            'admin'       => true,
            'menu_item'   => 'emoticons',
            'parent_menu' => 'sys_menu',
        ]
    );
} else {
    $message = __('Error updating cache');
    echo $view->render(
        'system::pages/result',
        [
            'title'       => $title,
            'type'        => 'alert-danger',
            'message'     => $message,
            'back_url'    => '/admin/',
            'admin'       => true,
            'menu_item'   => 'emoticons',
            'parent_menu' => 'sys_menu',
        ]
    );
}
