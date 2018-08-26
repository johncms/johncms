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
Переключаем режимы работы
-----------------------------------------------------------------
*/
$array = array(
    'activity'  => 'includes/profile',
    'ban'       => 'includes/profile',
    'edit'      => 'includes/profile',
    'images'    => 'includes/profile',
    'info'      => 'includes/profile',
    'ip'        => 'includes/profile',
    'guestbook' => 'includes/profile',
    'karma'     => 'includes/profile',
    'office'    => 'includes/profile',
    'password'  => 'includes/profile',
    'reset'     => 'includes/profile',
    'settings'  => 'includes/profile',
    'stat'      => 'includes/profile',
    'friends'   => 'includes/profile'
);
$path = !empty($array[$act]) ? $array[$act] . '/' : '';
if (array_key_exists($act, $array) && file_exists($path . $act . '.php')) {
    require_once($path . $act . '.php');
} else {
    /*
    -----------------------------------------------------------------
    Анкета пользователя
    -----------------------------------------------------------------
    */
    $headmod = 'profile,' . $user['id'];
    $textl = $lng['profile'] . ': ' . htmlspecialchars($user['name']);
    require('../incfiles/head.php');
    echo '<div class="phdr"><b>' . ($user['id'] != $user_id ? $lng_profile['user_profile'] : $lng_profile['my_profile']) . '</b></div>';

    // Меню анкеты
    $menu = array();
    if ($user['id'] == $user_id || $rights == 9 || ($rights == 7 && $rights > $user['rights'])) {
        $menu[] = '<a href="profile.php?act=edit&amp;user=' . $user['id'] . '">' . $lng['edit'] . '</a>';
    }
    if ($user['id'] != $user_id && $rights >= 7 && $rights > $user['rights']) {
        $menu[] = '<a href="' . $set['homeurl'] . '/' . $set['admp'] . '/index.php?act=usr_del&amp;id=' . $user['id'] . '">' . $lng['delete'] . '</a>';
    }
    if ($user['id'] != $user_id && $rights > $user['rights']) {
        $menu[] = '<a href="profile.php?act=ban&amp;mod=do&amp;user=' . $user['id'] . '">' . $lng['ban_do'] . '</a>';
    }
    if (!empty($menu)) {
        echo '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
    }

    //Уведомление о дне рожденья
    if ($user['dayb'] == date('j', time()) && $user['monthb'] == date('n', time())) {
        echo '<div class="gmenu">' . $lng['birthday'] . '!!!</div>';
    }

    // Информация о юзере
    $arg = array(
        'lastvisit' => 1,
        'iphist'    => 1,
        'header'    => '<b>ID:' . $user['id'] . '</b>'
    );

    if ($user['id'] != core::$user_id) {
        $arg['footer'] = '<span class="gray">' . core::$lng['where'] . ':</span> ' . functions::display_place($user['id'], $user['place']);
    }

    echo '<div class="user"><p>' . functions::display_user($user, $arg) . '</p></div>';
    // Если юзер ожидает подтверждения регистрации, выводим напоминание
    if ($rights >= 7 && !$user['preg'] && empty($user['regadm'])) {
        echo '<div class="rmenu">' . $lng_profile['awaiting_registration'] . '</div>';
    }
    // Карма
    if ($set_karma['on']) {
        $karma = $user['karma_plus'] - $user['karma_minus'];
        if ($karma > 0) {
            $images = ($user['karma_minus'] ? ceil($user['karma_plus'] / $user['karma_minus']) : $user['karma_plus']) > 10 ? '2' : '1';
            echo '<div class="gmenu">';
        } else if ($karma < 0) {
            $images = ($user['karma_plus'] ? ceil($user['karma_minus'] / $user['karma_plus']) : $user['karma_minus']) > 10 ? '-2' : '-1';
            echo '<div class="rmenu">';
        } else {
            $images = 0;
            echo '<div class="menu">';
        }
        echo '<table  width="100%"><tr><td width="22" valign="top"><img src="' . $set['homeurl'] . '/images/k_' . $images . '.gif"/></td><td>' .
            '<b>' . $lng['karma'] . ' (' . $karma . ')</b>' .
            '<div class="sub">' .
            '<span class="green"><a href="profile.php?act=karma&amp;user=' . $user['id'] . '&amp;type=1">' . $lng['vote_for'] . ' (' . $user['karma_plus'] . ')</a></span> | ' .
            '<span class="red"><a href="profile.php?act=karma&amp;user=' . $user['id'] . '">' . $lng['vote_against'] . ' (' . $user['karma_minus'] . ')</a></span>';
        if ($user['id'] != $user_id) {
            if (!$datauser['karma_off'] && (!$user['rights'] || ($user['rights'] && !$set_karma['adm'])) && $user['ip'] != $datauser['ip']) {
                $sum = $db->query("SELECT SUM(`points`) FROM `karma_users` WHERE `user_id` = '$user_id' AND `time` >= '" . $datauser['karma_time'] . "'")->fetchColumn();
                $count = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `user_id` = '$user_id' AND `karma_user` = '" . $user['id'] . "' AND `time` > '" . (time() - 86400) . "'")->fetchColumn();
                if (!$ban && $datauser['postforum'] >= $set_karma['forum'] && $datauser['total_on_site'] >= $set_karma['karma_time'] && ($set_karma['karma_points'] - $sum) > 0 && !$count) {
                    echo '<br /><a href="profile.php?act=karma&amp;mod=vote&amp;user=' . $user['id'] . '">' . $lng['vote'] . '</a>';
                }
            }
        } else {
            $total_karma = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '$user_id' AND `time` > " . (time() - 86400))->fetchColumn();
            if ($total_karma > 0) {
                echo '<br /><a href="profile.php?act=karma&amp;mod=new">' . $lng['responses_new'] . '</a> (' . $total_karma . ')';
            }
        }
        echo '</div></td></tr></table></div>';
    }
    // Меню выбора
    $total_photo = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();
    echo '<div class="list2"><p>' .
        '<div>' . functions::image('contacts.png') . '<a href="profile.php?act=info&amp;user=' . $user['id'] . '">' . $lng['information'] . '</a></div>' .
        '<div>' . functions::image('activity.gif') . '<a href="profile.php?act=activity&amp;user=' . $user['id'] . '">' . $lng_profile['activity'] . '</a></div>' .
        '<div>' . functions::image('rate.gif') . '<a href="profile.php?act=stat&amp;user=' . $user['id'] . '">' . $lng['statistics'] . '</a></div>';
    $bancount = $db->query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();

    if ($bancount) {
        echo '<div><img src="../images/block.gif" width="16" height="16"/>&#160;<a href="profile.php?act=ban&amp;user=' . $user['id'] . '">' . $lng['infringements'] . '</a> (' . $bancount . ')</div>';
    }

    $total_friends = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='{$user['id']}' AND `type`='2' AND `friends`='1'")->fetchColumn();
    echo '<br />' .
        '<div>' . functions::image('photo.gif') . '<a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng['photo_album'] . '</a>&#160;(' . $total_photo . ')</div>' .
        '<div>' . functions::image('guestbook.gif') . '<a href="profile.php?act=guestbook&amp;user=' . $user['id'] . '">' . $lng['guestbook'] . '</a>&#160;(' . $user['comm_count'] . ')</div>' .
        '<div>' . functions::image('users.png') . '<a href="profile.php?act=friends&amp;user=' . $user['id'] . '">' . $lng_profile['friends'] . '</a>&#160;(' . $total_friends . ')</div>' .
        '</p></div>';
    if ($user['id'] != $user_id) {
        echo '<div class="menu"><p>';
        // Контакты
        if (!functions::is_ignor($user['id']) && functions::is_contact($user['id']) != 2) {
            if (!functions::is_friend($user['id'])) {
                $fr_in = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `type`='2' AND `from_id`='$user_id' AND `user_id`='{$user['id']}'")->fetchColumn();
                $fr_out = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `type`='2' AND `user_id`='$user_id' AND `from_id`='{$user['id']}'")->fetchColumn();
                if ($fr_in == 1) {
                    $friend = '<a class="underline" href="profile.php?act=friends&amp;do=ok&amp;id=' . $user['id'] . '">' . $lng_profile['confirm_friendship'] . '</a> | <a class="underline" href="profile.php?act=friends&amp;do=no&amp;id=' . $user['id'] . '">' . $lng_profile['decline_friendship'] . '</a>';
                } else if ($fr_out == 1) {
                    $friend = '<a class="underline" href="profile.php?act=friends&amp;do=cancel&amp;id=' . $user['id'] . '">' . $lng_profile['canceled_demand_friend'] . '</a>';
                } else {
                    $friend = '<a href="profile.php?act=friends&amp;do=add&amp;id=' . $user['id'] . '">' . $lng_profile['in_friend'] . '</a>';
                }
            } else {
                $friend = '<a href="profile.php?act=friends&amp;do=delete&amp;id=' . $user['id'] . '">' . $lng_profile['remov_friend'] . '</a>';
            }
            echo '<div>' . functions::image('add.gif') . $friend . '</div>';
        }

        if (functions::is_contact($user['id']) != 2) {
            if (!functions::is_contact($user['id'])) {
                echo '<div><img src="../images/users.png" width="16" height="16"/>&#160;<a href="../mail/index.php?id=' . $user['id'] . '">' . $lng_profile['add_contacts'] . '</a></div>';
            } else {
                echo '<div><img src="../images/users.png" width="16" height="16"/>&#160;<a href="../mail/index.php?act=deluser&amp;id=' . $user['id'] . '">' . $lng_profile['delete_contacts'] . '</a></div>';
            }
        }

        if (functions::is_contact($user['id']) != 2) {
            echo '<div><img src="../images/del.png" width="16" height="16"/>&#160;<a href="../mail/index.php?act=ignor&amp;id=' . $user['id'] . '&amp;add">' . $lng_profile['add_ignor'] . '</a></div>';
        } else {
            echo '<div><img src="../images/del.png" width="16" height="16"/>&#160;<a href="../mail/index.php?act=ignor&amp;id=' . $user['id'] . '&amp;del">' . $lng_profile['delete_ignor'] . '</a></div>';
        }
        echo '</p>';

        if (!functions::is_ignor($user['id']) && functions::is_contact($user['id']) != 2 && empty($ban['1']) && empty($ban['3'])) {
            echo '<p><form action="../mail/index.php?act=write&amp;id=' . $user['id'] . '" method="post"><input type="submit" value="' . $lng['write'] . '" style="margin-left: 18px"/></form></p>';
        }
        echo '</div>';
    }
    echo '<div class="phdr"><a href="index.php">' . $lng['users'] . '</a></div>';
}

require_once('../incfiles/end.php');