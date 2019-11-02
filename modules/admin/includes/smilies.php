<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNADM') || die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

ob_start();

echo '<div class="phdr"><a href="./"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Smilies') . '</div>';

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

// Записываем в файл Кэша
if (file_put_contents(CACHE_PATH . 'smilies-list.cache', serialize($smileys))) {
    echo '<div class="gmenu"><p>' . _t('Smilie cache updated successfully') . '</p></div>';
} else {
    echo '<div class="rmenu"><p>' . _t('Error updating cache') . '</p></div>';
}
$total = count($smileys['adm']) + count($smileys['usr']);
echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>' .
    '<p><a href="./">' . _t('Admin Panel') . '</a></p>';

echo $view->render('system::app/old_content', [
    'title'   => _t('Admin Panel'),
    'content' => ob_get_clean(),
]);
