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

$headmod = 'news';
require('../incfiles/core.php');
$lng_news = core::load_lng('news'); // Загружаем язык модуля
$textl = $lng['news'];
require('../incfiles/head.php');
switch ($do) {
    case 'add':
        /*
        -----------------------------------------------------------------
        Добавление новости
        -----------------------------------------------------------------
        */
        if ($rights >= 6) {
            echo '<div class="phdr"><a href="index.php"><b>' . $lng['news'] . '</b></a> | ' . $lng['add'] . '</div>';
            if (isset($_POST['submit'])) {
                $error = array();
                $name = isset($_POST['name']) ? functions::checkin($_POST['name'], 1) : false;
                $text = isset($_POST['text']) ? functions::checkin($_POST['text']) : false;
                if (!$name) {
                    $error[] = $lng_news['error_title'];
                }
                if (!$text) {
                    $error[] = $lng_news['error_text'];
                }
                $flood = functions::antiflood();
                if ($flood) {
                    $error[] = $lng['error_flood'] . ' ' . $flood . '&#160;' . $lng['seconds'];
                }
                if (!$error) {
                    $rid = 0;
                    if (!empty($_POST['pf']) && ($_POST['pf'] != '0')) {
                        $pf = intval($_POST['pf']);
                        $rz = isset($_POST['rz']) && is_array($_POST['rz']) ? $_POST['rz'] : [];
                        $arr = [];
                        $stmt = $db->query("SELECT * FROM `forum` WHERE `refid` = '$pf' AND `type` = 'r'");
                        while ($pr1 = $stmt->fetch()) {
                            $arr[] = $pr1['id'];
                        }
                        foreach ($rz as $v) {
                            if (in_array($v, $arr)) {
                                $stmt = $db->prepare("INSERT INTO `forum` SET
                                    `refid` = '$v',
                                    `type` = 't',
                                    `time` = '" . time() . "',
                                    `user_id` = '$user_id',
                                    `from` = ?,
                                    `text` = ?
                                ");
                                $stmt->execute([
                                    $login,
                                    $name
                                ]);
                                $rid = $db->lastInsertId();
                                $stmt = $db->prepare("INSERT INTO `forum` SET
                                    `refid` = '$rid',
                                    `type` = 'm',
                                    `time` = '" . time() . "',
                                    `user_id` = '$user_id',
                                    `from` = ?,
                                    `ip` = '" . long2ip($ip) . "',
                                    `soft` = ?,
                                    `text` = ?
                                ");
                                $stmt->execute([
                                    $login,
                                    $agn,
                                    $text
                                ]);
                            }
                        }
                    }
                    $stmt = $db->prepare("INSERT INTO `news` SET
                        `time` = '" . time() . "',
                        `avt` = ?,
                        `name` = ?,
                        `text` = ?,
                        `kom` = '$rid'
                    ");
                    $stmt->execute([
                        $login,
                        $name,
                        $text
                    ]);
                    $db->exec("UPDATE `users` SET
                        `lastpost` = '" . time() . "'
                        WHERE `id` = '$user_id'
                    ");
                    echo '<p>' . $lng_news['article_added'] . '<br /><a href="index.php">' . $lng_news['to_news'] . '</a></p>';
                } else {
                    echo functions::display_error($error, '<a href="index.php">' . $lng_news['to_news'] . '</a>');
                }
            } else {
                echo '<form action="index.php?do=add" method="post"><div class="menu">' .
                     '<p><h3>' . $lng_news['article_title'] . '</h3>' .
                     '<input type="text" name="name"/></p>' .
                     '<p><h3>' . $lng['text'] . '</h3>' .
                     '<textarea rows="' . $set_user['field_h'] . '" name="text"></textarea></p>' .
                     '<p><h3>' . $lng_news['discuss'] . '</h3>';
                $stmt = $db->query("SELECT * FROM `forum` WHERE `type` = 'f' ORDER BY `realid` ASC");
                echo '<input type="radio" name="pf" value="0" checked="checked" />' . $lng_news['discuss_off'] . '<br />';
                while ($fr1 = $stmt->fetch()) {
                    echo '<input type="radio" name="pf" value="' . $fr1['id'] . '"/>' . _e($fr1['text']) . '<select name="rz[]">';
                    $stmt_2 = $db->query("SELECT * FROM `forum` WHERE `type` = 'r' AND `refid` = '" . $fr1['id'] . "' ORDER BY `realid` ASC");
                    while ($pr1 = $stmt_2->fetch()) {
                        echo '<option value="' . $pr1['id'] . '">' . _e($pr1['text']) . '</option>';
                    }
                    echo '</select><br/>';
                }
                echo '</p></div><div class="bmenu">' .
                     '<input type="submit" name="submit" value="' . $lng['save'] . '"/>' .
                     '</div></form>' .
                     '<p><a href="index.php">' . $lng_news['to_news'] . '</a></p>';
            }
        } else {
            header("location: index.php"); exit;
        }
        break;

    case 'edit':
        /*
        -----------------------------------------------------------------
        Редактирование новости
        -----------------------------------------------------------------
        */
        if ($rights >= 6) {
            echo '<div class="phdr"><a href="index.php"><b>' . $lng['news'] . '</b></a> | ' . $lng['edit'] . '</div>';
            if (!$id) {
                echo functions::display_error($lng['error_wrong_data'], '<a href="index.php">' . $lng_news['to_news'] . '</a>');
                require('../incfiles/end.php');
                exit;
            }
            if (isset($_POST['submit'])) {
                $error = array();
                $name = isset($_POST['name']) ? functions::checkin($_POST['name'], 1) : false;
                $text = isset($_POST['text']) ? functions::checkin($_POST['text']) : false;
                if (!$name) {
                    $error[] = $lng_news['error_title'];
                }
                if (!$text) {
                    $error[] = $lng_news['error_text'];
                }
                if (!$error) {
                    $stmt = $db->prepare("UPDATE `news` SET
                        `name` = ?,
                        `text` = ?
                        WHERE `id` = '$id'
                    ");
                    $stmt->execute([
                        $name,
                        $text
                    ]);
                } else {
                    echo functions::display_error($error, '<a href="index.php?act=edit&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
                }
                echo '<p>' . $lng_news['article_changed'] . '<br /><a href="index.php">' . $lng['continue'] . '</a></p>';
            } else {
                $res = $db->query("SELECT * FROM `news` WHERE `id` = '$id' LIMIT 1")->fetch();
                echo '<div class="menu"><form action="index.php?do=edit&amp;id=' . $id . '" method="post">' .
                     '<p><h3>' . $lng_news['article_title'] . '</h3>' .
                     '<input type="text" name="name" value="' . _e($res['name']) . '"/></p>' .
                     '<p><h3>' . $lng['text'] . '</h3>' .
                     '<textarea rows="' . $set_user['field_h'] . '" name="text">' . _e($res['text']) . '</textarea></p>' .
                     '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p>' .
                     '</form></div>' .
                     '<div class="phdr"><a href="index.php">' . $lng_news['to_news'] . '</a></div>';
            }
        } else {
            header('location: index.php'); exit;
        }
        break;

    case 'clean':
        /*
        -----------------------------------------------------------------
        Чистка новостей
        -----------------------------------------------------------------
        */
        if ($rights >= 7) {
            echo '<div class="phdr"><a href="index.php"><b>' . $lng_news['site_news'] . '</b></a> | ' . $lng['clear'] . '</div>';
            if (isset($_POST['submit'])) {
                $cl = isset($_POST['cl']) ? intval($_POST['cl']) : '';
                switch ($cl) {
                    case '1':
                        // Чистим новости, старше 1 недели
                        $db->exec("DELETE FROM `news` WHERE `time`<='" . (time() - 604800) . "'");
                        $db->query("OPTIMIZE TABLE `news`");
                        echo '<p>' . $lng_news['clear_week_confirmation'] . '</p><p><a href="index.php">' . $lng_news['to_news'] . '</a></p>';
                        break;

                    case '2':
                        // Проводим полную очистку
                        $db->exec("TRUNCATE TABLE `news`");
                        echo '<p>' . $lng_news['clear_all_confirmation'] . '</p><p><a href="index.php">' . $lng_news['to_news'] . '</a></p>';
                        break;
                    default :
                        // Чистим сообщения, старше 1 месяца
                        $db->exec("DELETE FROM `news` WHERE `time`<='" . (time() - 2592000) . "'");
                        $db->query("OPTIMIZE TABLE `news`;");
                        echo '<p>' . $lng_news['clear_month_confirmation'] . '</p><p><a href="index.php">' . $lng_news['to_news'] . '</a></p>';
                }
            } else {
                echo '<div class="menu"><form id="clean" method="post" action="index.php?do=clean">' .
                     '<p><h3>' . $lng['clear_param'] . '</h3>' .
                     '<input type="radio" name="cl" value="0" checked="checked" />' . $lng_news['clear_month'] . '<br />' .
                     '<input type="radio" name="cl" value="1" />' . $lng_news['clear_week'] . '<br />' .
                     '<input type="radio" name="cl" value="2" />' . $lng['clear_all'] . '</p>' .
                     '<p><input type="submit" name="submit" value="' . $lng['clear'] . '" /></p>' .
                     '</form></div>' .
                     '<div class="phdr"><a href="index.php">' . $lng['cancel'] . '</a></div>';
            }
        } else {
            header("location: index.php"); exit;
        }
        break;

    case 'del':
        /*
        -----------------------------------------------------------------
        Удаление новости
        -----------------------------------------------------------------
        */
        if ($rights >= 6) {
            echo '<div class="phdr"><a href="index.php"><b>' . $lng['site_news'] . '</b></a> | ' . $lng['delete'] . '</div>';
            if (isset($_GET['yes'])) {
                $db->exec("DELETE FROM `news` WHERE `id` = '$id'");
                echo '<p>' . $lng_news['article_deleted'] . '<br/><a href="index.php">' . $lng_news['to_news'] . '</a></p>';
            } else {
                echo '<p>' . $lng['delete_confirmation'] . '<br/>' .
                     '<a href="index.php?do=del&amp;id=' . $id . '&amp;yes">' . $lng['delete'] . '</a> | <a href="index.php">' . $lng['cancel'] . '</a></p>';
            }
        } else {
            header("location: index.php"); exit;
        }
        break;

    default:
        /*
        -----------------------------------------------------------------
        Вывод списка новостей
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><b>' . $lng['site_news'] . '</b></div>';
        if ($rights >= 6)
            echo '<div class="topmenu"><a href="index.php?do=add">' . $lng['add'] . '</a> | <a href="index.php?do=clean">' . $lng['clear'] . '</a></div>';
        $total = $db->query("SELECT COUNT(*) FROM `news`")->fetchColumn();
        $stmt = $db->query("SELECT * FROM `news` ORDER BY `time` DESC LIMIT $start, $kmess");
        $i = 0;
        while ($res = $stmt->fetch()) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            $text = functions::checkout($res['text'], 1, 1);
            if ($set_user['smileys']) {
                $text = functions::smileys($text, 1);
            }
            echo '<h3>' . _e($res['name']) . '</h3>' .
                 '<span class="gray"><small>' . $lng['author'] . ': ' . $res['avt'] . ' (' . functions::display_date($res['time']) . ')</small></span>' .
                 '<br />' . $text . '<div class="sub">';
            if ($res['kom'] != 0 && $res['kom'] != "") {
                $komm = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `refid` = '" . $res['kom'] . "'")->fetchColumn() - 1;
                if ($komm >= 0) {
                    echo '<a href="../forum/?id=' . $res['kom'] . '">' . $lng_news['discuss_on_forum'] . ' (' . $komm . ')</a><br/>';
                }
            }
            if ($rights >= 6) {
                echo '<a href="index.php?do=edit&amp;id=' . $res['id'] . '">' . $lng['edit'] . '</a> | ' .
                     '<a href="index.php?do=del&amp;id=' . $res['id'] . '">' . $lng['delete'] . '</a>';
            }
            echo '</div></div>';
            ++$i;
        }
        echo '<div class="phdr">' . $lng['total'] . ':&#160;' . $total . '</div>';
        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('index.php?', $start, $total, $kmess) . '</div>' .
                 '<p><form action="index.php" method="post">' .
                 '<input type="text" name="page" size="2"/>' .
                 '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
        }
}

require('../incfiles/end.php');
