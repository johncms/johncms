<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');
$lng_karma = core::load_lng('karma');
$textl = $lng['karma'];
require('../incfiles/head.php');
if ($set_karma['on']) {
    switch ($mod) {
        case 'vote':
            /*
            -----------------------------------------------------------------
            Отдаем голос за пользователя
            -----------------------------------------------------------------
            */
            if (!$datauser['karma_off'] && !$ban) {
                $error = array ();
                if ($user['rights'] && $set_karma['adm'])
                    $error[] = $lng_karma['error_not_for_admins'];
                if ($user['ip'] == $ip)
                    $error[] = $lng_karma['error_rogue'];
                if ($datauser['total_on_site'] < $set_karma['karma_time'] || $datauser['postforum'] < $set_karma['forum'])
                    $error[] = $lng_karma['error_terms_1'] . ' '
                        . ($set_karma['time'] ? ($set_karma['karma_time'] / 3600) . $lng['hours'] : ($set_karma['karma_time'] / 86400) . $lng['days']) . ' ' . $lng_karma['error_terms_2'] . ' ' . $set_karma['forum'] . ' '
                        . $lng_karma['posts'];
                $count = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `user_id` = '$user_id' AND `karma_user` = '" . $user['id'] . "' AND `time` > '" . (time() - 86400) . "'")->fetchColumn();
                if ($count)
                    $error[] = $lng_karma['error_terms_3'];
                $sum = $db->query("SELECT SUM(`points`) FROM `karma_users` WHERE `user_id` = '$user_id' AND `time` >= '" . $datauser['karma_time'] . "'")->fetchColumn();
                if (($set_karma['karma_points'] - $sum) <= 0)
                    $error[] = $lng_karma['error_limit'] . ' ' . date('d.m.y / H:i:s', ($datauser['karma_time'] + 86400));
                if ($error) {
                    echo functions::display_error($error, '<a href="profile.php?user=' . $user['id'] . '">' . $lng['back'] . '</a>');
                } else {
                    if (isset($_POST['submit'])) {
                        $text = isset($_POST['text']) ? mb_substr(trim($_POST['text']), 0, 500) : '';
                        $type = intval($_POST['type']) ? 1 : 0;
                        $points = abs(intval($_POST['points']));
                        if (!$points || $points > ($set_karma['karma_points'] - $sum)) {
                            $points = 1;
                        }
                        $stmt = $db->prepare("INSERT INTO `karma_users` SET
                            `user_id` = '$user_id',
                            `name` = ?,
                            `karma_user` = '" . $user['id'] . "',
                            `points` = '$points',
                            `type` = '$type',
                            `time` = '" . time() . "',
                            `text` = ?
                        ");
                        $stmt->execute([
                            $login,
                            $text
                        ]);
                        $sql = $type ? "`karma_plus` = '" . ($user['karma_plus'] + $points) . "'" : "`karma_minus` = '" . ($user['karma_minus'] + $points) . "'";
                        $db->exec("UPDATE `users` SET $sql WHERE `id` = '" . $user['id'] . "'");
                        echo '<div class="gmenu">' . $lng_karma['done'] . '!<br /><a href="profile.php?user=' . $user['id'] . '">' . $lng['continue'] . '</a></div>';
                    } else {
                        echo '<div class="phdr"><b>' . $lng_karma['vote_to'] . ' ' . $res['name'] . '</b>: ' . functions::checkout($user['name']) . '</div>' .
                            '<form action="profile.php?act=karma&amp;mod=vote&amp;user=' . $user['id'] . '" method="post">' .
                            '<div class="gmenu"><b>' . $lng_karma['vote_type'] . ':</b><br />' .
                            '<input name="type" type="radio" value="1" checked="checked"/> ' . $lng_karma['plus'] . '<br />' .
                            '<input name="type" type="radio" value="0"/> ' . $lng_karma['minus'] . '<br />' .
                            '<b>' . $lng_karma['vote_qty'] . ':</b><br />' .
                            '<select size="1" name="points">';
                        for ($i = 1; $i < ($set_karma['karma_points'] - $sum + 1); $i++) {
                            echo '<option value="' . $i . '">' . $i . '</option>';
                        }
                        echo '</select><b><br />' . $lng_karma['comment'] . ':</b><br />' .
                            '<input name="text" type="text" value=""/><br />' .
                            '<small>' . $lng['minmax_2_500'] . '</small>' .
                            '<p><input type="submit" name="submit" value="' . $lng['vote'] . '"/></p>' .
                            '</div></form>' .
                            '<div class="list2"><a href="profile.php?user=' . $user['id'] . '">' . $lng['profile'] . '</a></div>';
                    }
                }
            } else {
                echo functions::display_error($lng_karma['error_forbidden'], '<a href="profile.php?user=' . $user['id'] . '">' . $lng['back'] . '</a>');
            }
            break;

        case 'delete':
            /*
            -----------------------------------------------------------------
            Удаляем отдельный голос
            -----------------------------------------------------------------
            */
            if ($rights == 9) {
                $type = isset($_GET['type']) ? abs(intval($_GET['type'])) : NULL;
                $stmt = $db->query("SELECT * FROM `karma_users` WHERE `id` = '$id' AND `karma_user` = '" . $user['id'] . "'");
                if ($stmt->rowCount()) {
                    $res = $stmt->fetch();
                    if (isset($_GET['yes'])) {
                        $db->exec("DELETE FROM `karma_users` WHERE `id` = '$id'");
                        //TODO: Доработать калькуляцию
                        if($res['type']){
                            $sql = "`karma_plus` = '" . ($user['karma_plus'] > $res['points'] ? $user['karma_plus'] - $res['points'] : 0) . "'";
                        } else {
                            $sql = "`karma_minus` = '" . ($user['karma_minus'] > $res['points'] ? $user['karma_minus'] - $res['points'] : 0) . "'";
                        }
                        $db->exec("UPDATE `users` SET $sql WHERE `id` = '" . $user['id'] . "'");
                        header('Location: profile.php?act=karma&user=' . $user['id'] . '&type=' . $type); exit;
                    } else {
                        echo '<div class="rmenu"><p>' . $lng_karma['deletion_warning'] . '?<br/>' .
                            '<a href="profile.php?act=karma&amp;mod=delete&amp;user=' . $user['id'] . '&amp;id=' . $id . '&amp;type=' . $type . '&amp;yes">' . $lng['delete'] . '</a> | ' .
                            '<a href="profile.php?act=karma&amp;user=' . $user['id'] . '&amp;type=' . $type . '">' . $lng['cancel'] . '</a></p></div>';
                    }
                }
            }
            break;

        case 'clean':
            /*
            -----------------------------------------------------------------
            Очищаем все голоса за пользователя
            -----------------------------------------------------------------
            */
            if ($rights == 9) {
                if (isset($_GET['yes'])) {
                    $db->exec("DELETE FROM `karma_users` WHERE `karma_user` = '" . $user['id'] . "'");
                    $db->query("OPTIMIZE TABLE `karma_users`");
                    $db->exec("UPDATE `users` SET `karma_plus` = '0', `karma_minus` = '0' WHERE `id` = '" . $user['id'] . "'");
                    header('Location: profile.php?user=' . $user['id']); exit;
                } else {
                    echo '<div class="rmenu"><p>' . $lng_karma['clear_warning'] . '?<br/>' .
                        '<a href="profile.php?act=karma&amp;mod=clean&amp;user=' . $user['id'] . '&amp;yes">' . $lng['delete'] . '</a> | ' .
                        '<a href="profile.php?act=karma&amp;user=' . $user['id'] . '">' . $lng['cancel'] . '</a></p></div>';
                }
            }
            break;

        case 'new':
            /*
            -----------------------------------------------------------------
            Список новых отзывов (комментариев)
            -----------------------------------------------------------------
            */
            echo '<div class="phdr"><a href="profile.php?act=karma&amp;type=2"><b>' . $lng['karma'] . '</b></a> | ' . $lng_karma['new_responses'] . '</div>';
            $total = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '$user_id' AND `time` > " . (time() - 86400))->fetchColumn();
            if ($total) {
                $stmt = $db->query("SELECT * FROM `karma_users` WHERE `karma_user` = '$user_id' AND `time` > " . (time() - 86400) . " ORDER BY `time` DESC LIMIT $start, $kmess");
                while ($res = $stmt->fetch()) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo $res['type'] ? '<span class="green">+' . $res['points'] . '</span> ' : '<span class="red">-' . $res['points'] . '</span> ';
                    echo $user_id == $res['user_id'] || !$res['user_id'] ? '<b>' . $res['name'] . '</b>' : '<a href="profile.php?user=' . $res['user_id'] . '"><b>' . $res['name'] . '</b></a>';
                    echo ' <span class="gray">(' . functions::display_date($res['time']) . ')</span>';
                    if (!empty($res['text']))
                        echo '<div class="sub">' . functions::checkout($res['text']) . '</div>';
                    echo '</div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
            }
            echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<p>' . functions::display_pagination('profile.php?act=karma&amp;mod=new&amp;', $start, $total, $kmess) . '</p>' .
                    '<p><form action="profile.php?act=karma&amp;mod=new" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
            }
            echo '<p><a href="profile.php">' . $lng['profile'] . '</a></p>';
            break;

        default:
            /*
            -----------------------------------------------------------------
            Главная страница Кармы, список отзывов
            -----------------------------------------------------------------
            */
            $type = isset($_GET['type']) ? abs(intval($_GET['type'])) : 0;
            $menu = array (
                ($type == 2 ? '<b>' . $lng_karma['all'] . '</b>' : '<a href="profile.php?act=karma&amp;user=' . $user['id'] . '&amp;type=2">' . $lng_karma['all'] . '</a>'),
                ($type == 1 ? '<b>' . $lng_karma['positive'] . '</b>' : '<a href="profile.php?act=karma&amp;user=' . $user['id'] . '&amp;type=1">' . $lng_karma['positive'] . '</a>'),
                (!$type ? '<b>' . $lng_karma['negative'] . '</b>' : '<a href="profile.php?act=karma&amp;user=' . $user['id'] . '">' . $lng_karma['negative'] . '</a>')
            );
            echo '<div class="phdr"><a href="profile.php?user=' . $user['id'] . '"><b>' . $lng['profile'] . '</b></a> | ' . $lng['karma'] . '</div>' .
                '<div class="topmenu">' . functions::display_menu($menu) . '</div>' .
                '<div class="user"><p>' . functions::display_user($user, array ('iphide' => 1,)) . '</p></div>';
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
                '<span class="green">' . $lng['vote_for'] . ' (' . $user['karma_plus'] . ')</span> | ' .
                '<span class="red">' . $lng['vote_against'] . ' (' . $user['karma_minus'] . ')</span>';
            echo '</div></td></tr></table></div>';
            $total = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '" . $user['id'] . "'" . ($type == 2 ? "" : " AND `type` = '$type'"))->fetchColumn();
            if ($total) {
                $stmt = $db->query("SELECT * FROM `karma_users` WHERE `karma_user` = '" . $user['id'] . "'" . ($type == 2 ? "" : " AND `type` = '$type'") . " ORDER BY `time` DESC LIMIT $start, $kmess");
                $i = 0;
                while ($res = $stmt->fetch()) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo $res['type'] ? '<span class="green">+' . $res['points'] . '</span> ' : '<span class="red">-' . $res['points'] . '</span> ';
                    echo $user_id == $res['user_id'] || !$res['user_id'] ? '<b>' . $res['name'] . '</b>' : '<a href="profile.php?user=' . $res['user_id'] . '"><b>' . $res['name'] . '</b></a>';
                    echo ' <span class="gray">(' . functions::display_date($res['time']) . ')</span>';
                    if ($rights == 9)
                        echo ' <span class="red"><a href="profile.php?act=karma&amp;mod=delete&amp;user=' . $user['id'] . '&amp;id=' . $res['id'] . '&amp;type=' . $type . '">[X]</a></span>';
                    if (!empty($res['text']))
                        echo '<br />' . functions::smileys(functions::checkout($res['text']));
                    echo '</div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
            }
            echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<div class="topmenu">' . functions::display_pagination('profile.php?act=karma&amp;user=' . $user['id'] . '&amp;type=' . $type . '&amp;', $start, $total, $kmess) . '</div>' .
                    '<p><form action="profile.php?act=karma&amp;user=' . $user['id'] . '&amp;type=' . $type . '" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
            }
            echo '<p>' . ($rights == 9 ? '<a href="profile.php?act=karma&amp;user=' . $user['id'] . '&amp;mod=clean">' . $lng_karma['reset'] . '</a><br />' : '') .
                '<a href="profile.php?user=' . $user['id'] . '">' . $lng['profile'] . '</a></p>';
    }
}
