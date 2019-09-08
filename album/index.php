<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

define('_IN_JOHNCMS', 1);

$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';
$al = isset($_REQUEST['al']) ? abs(intval($_REQUEST['al'])) : null;
$img = isset($_REQUEST['img']) ? abs(intval($_REQUEST['img'])) : null;

require('../system/bootstrap.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

$textl = _t('Album');
$headmod = 'album';

$max_album = 20;
$max_photo = 400;

// Закрываем от неавторизованных юзеров
if (!$systemUser->isValid()) {
    require('../system/head.php');
    echo $tools->displayError(_t('For registered users only'));
    require('../system/end.php');
    exit;
}

// Получаем данные пользователя
$user = $tools->getUser(isset($_REQUEST['user']) ? abs(intval($_REQUEST['user'])) : 0);

if (!$user) {
    require('../system/head.php');
    echo $tools->displayError(_t('User does not exists'));
    require('../system/end.php');
    exit;
}

/**
 * Функция голосований за фотографии
 *
 * @param array $arg
 * @return bool|string
 */
function vote_photo(array $arg)
{
    /** @var Psr\Container\ContainerInterface $container */
    $container = App::getContainer();

    /** @var PDO $db */
    $db = $container->get(PDO::class);

    /** @var Johncms\Api\UserInterface $systemUser */
    $systemUser = $container->get(Johncms\Api\UserInterface::class);

    $rating = $arg['vote_plus'] - $arg['vote_minus'];

    if ($rating > 0) {
        $color = 'C0FFC0';
    } elseif ($rating < 0) {
        $color = 'F196A8';
    } else {
        $color = 'CCC';
    }

    $out = '<div class="gray">' . _t('Rating') . ': <span style="color:#000;background-color:#' . $color . '">&#160;&#160;<big><b>' . $rating . '</b></big>&#160;&#160;</span> ' .
        '(' . _t('Against') . ': ' . $arg['vote_minus'] . ', ' . _t('For') . ': ' . $arg['vote_plus'] . ')';

    if ($systemUser->id != $arg['user_id'] && empty($systemUser->ban) && $systemUser->postforum > 10 && $systemUser->total_on_site > 1200) {
        // Проверяем, имеет ли юзер право голоса
        $req = $db->query("SELECT * FROM `cms_album_votes` WHERE `user_id` = '" . $systemUser->id . "' AND `file_id` = '" . $arg['id'] . "' LIMIT 1");

        if (!$req->rowCount()) {
            $out .= '<br>' . _t('Vote') . ': <a href="?act=vote&amp;mod=minus&amp;img=' . $arg['id'] . '">&lt;&lt; -1</a> | <a href="?act=vote&amp;mod=plus&amp;img=' . $arg['id'] . '">+1 &gt;&gt;</a>';
        }
    }
    $out .= '</div>';

    return $out;
}

// Переключаем режимы работы
$array = [
    'comments'       => 'includes',
    'delete'         => 'includes',
    'edit'           => 'includes',
    'image_delete'   => 'includes',
    'image_download' => 'includes',
    'image_edit'     => 'includes',
    'image_move'     => 'includes',
    'image_upload'   => 'includes',
    'list'           => 'includes',
    'new_comm'       => 'includes',
    'show'           => 'includes',
    'sort'           => 'includes',
    'top'            => 'includes',
    'users'          => 'includes',
    'vote'           => 'includes',
];

$path = !empty($array[$act]) ? $array[$act] . '/' : '';

if (array_key_exists($act, $array) && file_exists($path . $act . '.php')) {
    require_once($path . $act . '.php');
} else {
    /** @var PDO $db */
    $db = $container->get(PDO::class);

    /** @var Johncms\Api\ConfigInterface $config */
    $config = $container->get(Johncms\Api\ConfigInterface::class);

    require('../system/head.php');
    $albumcount = $db->query("SELECT COUNT(DISTINCT `user_id`) FROM `cms_album_files`")->fetchColumn();
    $total_mans = $db->query("SELECT COUNT(DISTINCT `user_id`)
      FROM `cms_album_files`
      LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id`
      WHERE `users`.`sex` = 'm'
    ")->fetchColumn();
    $total_womans = $db->query("SELECT COUNT(DISTINCT `user_id`)
      FROM `cms_album_files`
      LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id`
      WHERE `users`.`sex` = 'zh'
    ")->fetchColumn();
    $newcount = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > '" . (time() - 259200) . "' AND `access` > '1'")->fetchColumn();
    echo '<div class="phdr"><b>' . _t('Photo Albums') . '</b></div>' .
        '<div class="gmenu"><p>' .
        $tools->image('users.png', ['width' => 16, 'height' => 16]) . '<a href="?act=top">' . _t('New Photos') . '</a> (' . $newcount . ')<br>' .
        $tools->image('talk.gif', ['width' => 16, 'height' => 16]) . '<a href="?act=top&amp;mod=last_comm">' . _t('New Comments') . '</a>' .
        '</p></div>' .
        '<div class="menu">' .
        '<p><h3><img src="' . $config->homeurl . '/images/users.png" width="16" height="16" class="left" />&#160;' . _t('Albums') . '</h3><ul>' .
        '<li><a href="?act=users&amp;mod=boys">' . _t('Guys') . '</a> (' . $total_mans . ')</li>' .
        '<li><a href="?act=users&amp;mod=girls">' . _t('Girls') . '</a> (' . $total_womans . ')</li>';

    if ($systemUser->isValid()) {
        echo '<li><a href="?act=list">' . _t('My Album') . '</a></li>';
    }

    echo '</ul></p>' .
        '<p><h3>' . $tools->image('rate.gif') . _t('Rating') . '</h3><ul>' .
        '<li><a href="?act=top&amp;mod=votes">' . _t('Top Votes') . '</a></li>' .
        '<li><a href="?act=top&amp;mod=downloads">' . _t('Top Downloads') . '</a></li>' .
        '<li><a href="?act=top&amp;mod=views">' . _t('Top Views') . '</a></li>' .
        '<li><a href="?act=top&amp;mod=comments">' . _t('Top Comments') . '</a></li>' .
        '<li><a href="?act=top&amp;mod=trash">' . _t('Top Worst') . '</a></li>' .
        '</ul></p>' .
        '</div>' .
        '<div class="phdr"><a href="index.php">' . _t('Users') . '</a></div>';
}

require('../system/end.php');
