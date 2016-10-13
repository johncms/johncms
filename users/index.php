<?php

define('_IN_JOHNCMS', 1);

$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

$headmod = 'users';
require('../incfiles/core.php');

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

// Закрываем от неавторизованных юзеров
if (!$user_id && !$set['active']) {
    require('../incfiles/head.php');
    echo functions::display_error(_t('For registered users only'));
    require('../incfiles/end.php');
    exit;
}

// Переключаем режимы работы
$array = [
    'admlist'  => 'includes',
    'birth'    => 'includes',
    'online'   => 'includes',
    'top'      => 'includes',
    'userlist' => 'includes',
];
$path = !empty($array[$act]) ? $array[$act] . '/' : '';

if (array_key_exists($act, $array) && file_exists($path . $act . '.php')) {
    require_once($path . $act . '.php');
} else {
    /** @var PDO $db */
    $db = $container->get(PDO::class);

    // Актив сайта
    $textl = _t('Community');
    require('../incfiles/head.php');

    $brth = $db->query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . date('j', time()) . "' AND `monthb` = '" . date('n', time()) . "' AND `preg` = '1'")->fetchColumn();
    $count_adm = $db->query("SELECT COUNT(*) FROM `users` WHERE `rights` > 0")->fetchColumn();

    echo '<div class="phdr"><b>' . _t('Community') . '</b></div>' .
        '<div class="gmenu"><form action="search.php" method="post">' .
        '<p><h3><img src="../images/search.png" width="16" height="16" class="left" />&#160;' . _t('Look for the User') . '</h3>' .
        '<input type="text" name="search"/>' .
        '<input type="submit" value="' . _t('Search') . '" name="submit" /><br />' .
        '<small>' . _t('The search is performed by Nickname and are case-insensitive.') . '</small></p></form></div>' .
        '<div class="menu"><p>' .
        functions::image('contacts.png', ['width' => 16, 'height' => 16]) . '<a href="index.php?act=userlist">' . _t('Users') . '</a> (' . counters::users() . ')<br />' .
        functions::image('users.png', ['width' => 16, 'height' => 16]) . '<a href="index.php?act=admlist">' . _t('Administration') . '</a> (' . $count_adm . ')<br>' .
        ($brth ? functions::image('award.png', ['width' => 16, 'height' => 16]) . '<a href="index.php?act=birth">' . _t('Birthdays') . '</a> (' . $brth . ')<br>' : '') .
        functions::image('photo.gif', ['width' => 16, 'height' => 16]) . '<a href="../album/index.php">' . _t('Photo Albums') . '</a> (' . counters::album() . ')<br>' .
        functions::image('rate.gif', ['width' => 16, 'height' => 16]) . '<a href="index.php?act=top">' . _t('Top Activity') . '</a></p>' .
        '</div>' .
        '<div class="phdr"><a href="index.php">' . _t('Back') . '</a></div>';
}

require_once('../incfiles/end.php');
