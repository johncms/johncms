<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

define('_IN_JOHNCMS', 1);

require('../incfiles/core.php');
$lng_profile = core::load_lng('profile');
$textl = $lng_profile['album'];
$headmod = 'album';

$max_album = 20;
$max_photo = 400;
$al = isset($_REQUEST['al']) ? abs(intval($_REQUEST['al'])) : null;
$img = isset($_REQUEST['img']) ? abs(intval($_REQUEST['img'])) : null;

/*
-----------------------------------------------------------------
Закрываем от неавторизованных юзеров
-----------------------------------------------------------------
*/
if (!$user_id) {
    require('../incfiles/head.php');
    echo functions::display_error($lng['access_guest_forbidden']);
    require('../incfiles/end.php');
    exit;
}

/*
-----------------------------------------------------------------
Получаем данные пользователя
-----------------------------------------------------------------
*/
$user = functions::get_user($user);
if (!$user) {
    require('../incfiles/head.php');
    echo functions::display_error($lng['user_does_not_exist']);
    require('../incfiles/end.php');
    exit;
}

/*
-----------------------------------------------------------------
Функция голосований за фотографии
-----------------------------------------------------------------
*/
function vote_photo($arg = null)
{
    global $lng, $datauser, $user_id, $ban;

    if ($arg) {
        $rating = $arg['vote_plus'] - $arg['vote_minus'];
        if ($rating > 0)
            $color = 'C0FFC0';
        elseif ($rating < 0)
            $color = 'F196A8';
        else
            $color = 'CCC';
        $out = '<div class="gray">' . $lng['rating'] . ': <span style="color:#000;background-color:#' . $color . '">&#160;&#160;<big><b>' . $rating . '</b></big>&#160;&#160;</span> ' .
            '(' . $lng['vote_against'] . ': ' . $arg['vote_minus'] . ', ' . $lng['vote_for'] . ': ' . $arg['vote_plus'] . ')';
        if ($user_id != $arg['user_id'] && !$ban && $datauser['postforum'] > 10 && $datauser['total_on_site'] > 1200) {
            // Проверяем, имеет ли юзер право голоса
            $stmt = core::$db->query("SELECT * FROM `cms_album_votes` WHERE `user_id` = '$user_id' AND `file_id` = '" . $arg['id'] . "' LIMIT 1");
            if (!$stmt->rowCount()) {
                $out .= '<br />' . $lng['vote'] . ': <a href="album.php?act=vote&amp;mod=minus&amp;img=' . $arg['id'] . '">&lt;&lt; -1</a> | ' .
                    '<a href="album.php?act=vote&amp;mod=plus&amp;img=' . $arg['id'] . '">+1 &gt;&gt;</a>';
            }
        }
        $out .= '</div>';

        return $out;
    } else {
        return false;
    }
}

/*
-----------------------------------------------------------------
Переключаем режимы работы
-----------------------------------------------------------------
*/
$array = array(
    'comments'       => 'includes/album',
    'delete'         => 'includes/album',
    'edit'           => 'includes/album',
    'image_delete'   => 'includes/album',
    'image_download' => 'includes/album',
    'image_edit'     => 'includes/album',
    'image_move'     => 'includes/album',
    'image_upload'   => 'includes/album',
    'list'           => 'includes/album',
    'new_comm'       => 'includes/album',
    'show'           => 'includes/album',
    'sort'           => 'includes/album',
    'top'            => 'includes/album',
    'users'          => 'includes/album',
    'vote'           => 'includes/album'
);
$path = !empty($array[$act]) ? $array[$act] . '/' : '';
if (array_key_exists($act, $array) && file_exists($path . $act . '.php')) {
    require_once($path . $act . '.php');
} else {
    require('../incfiles/head.php');
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
    echo '<div class="phdr"><b>' . $lng['photo_albums'] . '</b></div>' .
        '<div class="gmenu"><p>' .
        functions::image('users.png', array('width' => 16, 'height' => 16)) . '<a href="album.php?act=top">' . $lng_profile['new_photo'] . '</a> (' . $newcount . ')<br />' .
        functions::image('talk.gif', array('width' => 16, 'height' => 16)) . '<a href="album.php?act=top&amp;mod=last_comm">' . $lng_profile['new_comments'] . '</a>' .
        '</p></div>' .
        '<div class="menu">' .
        '<p><h3><img src="' . $set['homeurl'] . '/images/users.png" width="16" height="16" class="left" />&#160;' . $lng['albums'] . '</h3><ul>' .
        '<li><a href="album.php?act=users&amp;mod=boys">' . $lng['mans'] . '</a> (' . $total_mans . ')</li>' .
        '<li><a href="album.php?act=users&amp;mod=girls">' . $lng['womans'] . '</a> (' . $total_womans . ')</li>';
    if ($user_id) {
        echo '<li><a href="album.php?act=list">' . $lng_profile['my_album'] . '</a></li>';
    }
    echo '</ul></p>' .
        '<p><h3>' . functions::image('rate.gif') . $lng['rating'] . '</h3><ul>' .
        '<li><a href="album.php?act=top&amp;mod=votes">' . $lng_profile['top_votes'] . '</a></li>' .
        '<li><a href="album.php?act=top&amp;mod=downloads">' . $lng_profile['top_downloads'] . '</a></li>' .
        '<li><a href="album.php?act=top&amp;mod=views">' . $lng_profile['top_views'] . '</a></li>' .
        '<li><a href="album.php?act=top&amp;mod=comments">' . $lng_profile['top_comments'] . '</a></li>' .
        '<li><a href="album.php?act=top&amp;mod=trash">' . $lng_profile['top_trash'] . '</a></li>' .
        '</ul></p>' .
        '</div>' .
        '<div class="phdr"><a href="index.php">' . $lng['users'] . '</a></div>';
}
require('../incfiles/end.php');
?>