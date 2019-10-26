<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

$headmod = 'users';

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Закрываем от неавторизованных юзеров
if (! $systemUser->isValid() && ! $config->active) {
    require 'system/head.php';
    echo $tools->displayError(_t('For registered users only'));
    require 'system/end.php';
    exit;
}

// Переключаем режимы работы
$mods = [
    'admlist',
    'birth',
    'online',
    'top',
    'userlist',
];

if ($act && ($key = array_search($act, $mods)) !== false && file_exists(__DIR__ . '/includes/' . $mods[$key] . '.php')) {
    require __DIR__ . '/includes/' . $mods[$key] . '.php';
} else {
    /** @var PDO $db */
    $db = $container->get(PDO::class);

    /** @var Johncms\Counters $counters */
    $counters = $container->get('counters');

    // Актив сайта
    $textl = _t('Community');
    require 'system/head.php';

    $brth = $db->query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . date('j', time()) . "' AND `monthb` = '" . date('n', time()) . "' AND `preg` = '1'")->fetchColumn();
    $count_adm = $db->query('SELECT COUNT(*) FROM `users` WHERE `rights` > 0')->fetchColumn();

    echo '<div class="phdr"><b>' . _t('Community') . '</b></div>' .
        '<div class="gmenu"><form action="search.php" method="post">' .
        '<p><h3><img src="../images/search.png" width="16" height="16" class="left" />&#160;' . _t('Look for the User') . '</h3>' .
        '<input type="text" name="search"/>' .
        '<input type="submit" value="' . _t('Search') . '" name="submit" /><br />' .
        '<small>' . _t('The search is performed by Nickname and are case-insensitive.') . '</small></p></form></div>' .
        '<div class="menu"><p>' .
        $tools->image('contacts.png', ['width' => 16, 'height' => 16]) . '<a href="index.php?act=userlist">' . _t('Users') . '</a> (' . $container->get('counters')->users() . ')<br />' .
        $tools->image('users.png', ['width' => 16, 'height' => 16]) . '<a href="index.php?act=admlist">' . _t('Administration') . '</a> (' . $count_adm . ')<br>' .
        ($brth ? $tools->image('award.png', ['width' => 16, 'height' => 16]) . '<a href="index.php?act=birth">' . _t('Birthdays') . '</a> (' . $brth . ')<br>' : '') .
        $tools->image('photo.gif', ['width' => 16, 'height' => 16]) . '<a href="../album/">' . _t('Photo Albums') . '</a> (' . $counters->album() . ')<br>' .
        $tools->image('rate.gif', ['width' => 16, 'height' => 16]) . '<a href="index.php?act=top">' . _t('Top Activity') . '</a></p>' .
        '</div>';
}

require_once 'system/end.php';
