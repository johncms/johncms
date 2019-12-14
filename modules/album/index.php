<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\ToolsInterface;
use Johncms\System\Users\User;
use Johncms\View\Extension\Assets;
use Johncms\View\Render;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var Assets $assets
 * @var PDO $db
 * @var ToolsInterface $tools
 * @var User $user
 * @var Render $view
 */

$assets = di(Assets::class);
$db = di(PDO::class);
$user = di(User::class);
$tools = di(ToolsInterface::class);
$view = di(Render::class);

// Регистрируем папку с языками модуля
di(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';
$al = isset($_REQUEST['al']) ? abs((int) ($_REQUEST['al'])) : null;
$img = isset($_REQUEST['img']) ? abs((int) ($_REQUEST['img'])) : null;

$textl = _t('Album');

$max_album = 20;
$max_photo = 400;

// Закрываем от неавторизованных юзеров
if (! $user->isValid()) {
    echo $view->render(
        'system::app/old_content',
        [
            'title'   => $textl ?? '',
            'content' => $tools->displayError(_t('For registered users only')),
        ]
    );
    exit;
}

// Получаем данные пользователя
$foundUser = $tools->getUser(isset($_REQUEST['user']) ? abs((int) ($_REQUEST['user'])) : 0);

if (! $foundUser) {
    echo $view->render(
        'system::app/old_content',
        [
            'title'   => $textl ?? '',
            'content' => $tools->displayError(_t('User does not exists')),
        ]
    );
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
    global $db, $user;

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

    if ($user->id != $arg['user_id'] && empty($user->ban) && $user->postforum > 10 && $user->total_on_site > 1200) {
        // Проверяем, имеет ли юзер право голоса
        $req = $db->query("SELECT * FROM `cms_album_votes` WHERE `user_id` = '" . $user->id . "' AND `file_id` = '" . $arg['id'] . "' LIMIT 1");

        if (! $req->rowCount()) {
            $out .= '<br>' . _t('Vote') . ': <a href="?act=vote&amp;mod=minus&amp;img=' . $arg['id'] . '">&lt;&lt; -1</a> | <a href="?act=vote&amp;mod=plus&amp;img=' . $arg['id'] . '">+1 &gt;&gt;</a>';
        }
    }
    $out .= '</div>';

    return $out;
}

$actions = [
    'comments',
    'delete',
    'edit',
    'image_delete',
    'image_download',
    'image_edit',
    'image_move',
    'image_upload',
    'list',
    'new_comm',
    'show',
    'sort',
    'top',
    'users',
    'vote',
];

if (($key = array_search($act, $actions)) !== false) {
    require __DIR__ . '/includes/' . $actions[$key] . '.php';
} else {
    $albumcount = $db->query('SELECT COUNT(DISTINCT `user_id`) FROM `cms_album_files`')->fetchColumn();
    $total_mans = $db->query(
        "SELECT COUNT(DISTINCT `user_id`)
      FROM `cms_album_files`
      LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id`
      WHERE `users`.`sex` = 'm'
    "
    )->fetchColumn();
    $total_womans = $db->query(
        "SELECT COUNT(DISTINCT `user_id`)
      FROM `cms_album_files`
      LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id`
      WHERE `users`.`sex` = 'zh'
    "
    )->fetchColumn();
    $newcount = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > '" . (time() - 259200) . "' AND `access` > '1'")->fetchColumn();
    echo '<div class="phdr"><b>' . _t('Photo Albums') . '</b></div>' .
        '<div class="gmenu"><p>' . '<img src="' . $assets->url('images/old/user-ok.png') . '" alt="" class="icon">' .
        '<a href="?act=top">' . _t('New Photos') . '</a> (' . $newcount . ')<br>' .
        '<img src="' . $assets->url('images/old/talk.gif') . '" alt="" class="icon">' . '<a href="?act=top&amp;mod=last_comm">' . _t('New Comments') . '</a>' .
        '</p></div>' .
        '<div class="menu">' .
        '<p><h3><img src="' . $assets->url('images/old/users.png') . '" alt="" class="icon">' . _t('Albums') . '</h3><ul>' .
        '<li><a href="?act=users&amp;mod=boys">' . _t('Guys') . '</a> (' . $total_mans . ')</li>' .
        '<li><a href="?act=users&amp;mod=girls">' . _t('Girls') . '</a> (' . $total_womans . ')</li>';

    if ($user->isValid()) {
        echo '<li><a href="?act=list">' . _t('My Album') . '</a></li>';
    }

    echo '</ul></p>' .
        '<p><h3><img src="' . $assets->url('images/old/rate.gif') . '" alt="" class="icon">' . _t('Rating') . '</h3><ul>' .
        '<li><a href="?act=top&amp;mod=votes">' . _t('Top Votes') . '</a></li>' .
        '<li><a href="?act=top&amp;mod=downloads">' . _t('Top Downloads') . '</a></li>' .
        '<li><a href="?act=top&amp;mod=views">' . _t('Top Views') . '</a></li>' .
        '<li><a href="?act=top&amp;mod=comments">' . _t('Top Comments') . '</a></li>' .
        '<li><a href="?act=top&amp;mod=trash">' . _t('Top Worst') . '</a></li>' .
        '</ul></p>' .
        '</div>';
}

echo $view->render(
    'system::app/old_content',
    [
        'title'   => $textl ?? '',
        'content' => ob_get_clean(),
    ]
);
