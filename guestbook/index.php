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
$headmod = 'guestbook';
require('../incfiles/core.php');
if (isset($_SESSION['ref']))
    unset($_SESSION['ref']);

// Проверяем права доступа в Админ-Клуб
if (isset($_SESSION['ga']) && $rights < 1) {
    unset($_SESSION['ga']);
}

// Задаем заголовки страницы
$textl = isset($_SESSION['ga']) ? $lng['admin_club'] : $lng['guestbook'];
require('../incfiles/head.php');

// Если гостевая закрыта, выводим сообщение и закрываем доступ (кроме Админов)
if (!$set['mod_guest'] && $rights < 7) {
    echo '<div class="rmenu"><p>' . $lng['guestbook_closed'] . '</p></div>';
    require('../incfiles/end.php');
    exit;
}
switch ($act) {
    case 'delpost':
        /*
        -----------------------------------------------------------------
        Удаление отдельного поста
        -----------------------------------------------------------------
        */
        if ($rights >= 6 && $id) {
            if (isset($_GET['yes'])) {
                $db->exec("DELETE FROM `guest` WHERE `id`='" . $id . "'");
                header("Location: index.php"); exit;
            } else {
                echo '<div class="phdr"><a href="index.php"><b>' . $lng['guestbook'] . '</b></a> | ' . $lng['delete_message'] . '</div>' .
                    '<div class="rmenu"><p>' . $lng['delete_confirmation'] . '?<br/>' .
                    '<a href="index.php?act=delpost&amp;id=' . $id . '&amp;yes">' . $lng['delete'] . '</a> | ' .
                    '<a href="index.php">' . $lng['cancel'] . '</a></p></div>';
            }
        }
        break;

    case 'say':
        /*
        -----------------------------------------------------------------
        Добавление нового поста
        -----------------------------------------------------------------
        */
        $admset = isset($_SESSION['ga']) ? 1 : 0; // Задаем куда вставляем, в Админ клуб (1), или в Гастивуху (0)
        // Принимаем и обрабатываем данные
        $name = isset($_POST['name']) ? functions::checkin(mb_substr(trim($_POST['name']), 0, 20)) : '';
        $msg = isset($_POST['msg']) ? functions::checkin(mb_substr(trim($_POST['msg']), 0, 5000)) : '';
        $trans = isset($_POST['msgtrans']) ? 1 : 0;
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $from = $user_id ? $login : $name;
        // Транслит сообщения
        if ($trans)
            $msg = functions::trans($msg);
        // Проверяем на ошибки
        $error = array();
        $flood = FALSE;
        if (!isset($_POST['token']) || !isset($_SESSION['token']) || $_POST['token'] != $_SESSION['token']) {
            $error[] = $lng['error_wrong_data'];
        }
        if (!$user_id && empty($name))
            $error[] = $lng['error_empty_name'];
        if (empty($msg))
            $error[] = $lng['error_empty_message'];
        if (isset($ban['1']) || isset($ban['13']))
            $error[] = $lng['access_forbidden'];
        // CAPTCHA для гостей
        if (!$user_id && (empty($code) || mb_strlen($code) < 4 || $code != $_SESSION['code']))
            $error[] = $lng['error_wrong_captcha'];
        unset($_SESSION['code']);
        if ($user_id) {
            // Антифлуд для зарегистрированных пользователей
            $flood = functions::antiflood();
        } else {
            // Антифлуд для гостей
            $stmt = $db->prepare("SELECT `time` FROM `guest` WHERE `ip` = '$ip' AND `browser` = ? AND `time` > '" . (time() - 60) . "' LIMIT 1");
            $stmt->execute([
                $agn
            ]);
            if ($stmt->rowCount()) {
                $res = $stmt->fetch();
                $flood = time() - $res['time'];
            }
        }
        if ($flood)
            $error = $lng['error_flood'] . ' ' . $flood . '&#160;' . $lng['seconds'];
        if (!$error) {
            // Проверка на одинаковые сообщения
            $res = $db->query("SELECT `text` FROM `guest` WHERE `user_id` = '$user_id' ORDER BY `time` DESC LIMIT 1")->fetch();
            if ($res['text'] == $msg) {
                header("location: index.php"); exit;
            }
        }
        if (!$error) {
            // Вставляем сообщение в базу
            $stmt = $db->prepare("INSERT INTO `guest` SET
                `adm` = '$admset',
                `time` = '" . time() . "',
                `user_id` = '" . ($user_id ? $user_id : 0) . "',
                `name` = ?,
                `text` = ?,
                `ip` = '" . core::$ip . "',
                `browser` = ?,
                `otvet` = ''
            ");
            $stmt->execute([
                $from,
                $msg,
                $agn
            ]);
            // Фиксируем время последнего поста (антиспам)
            if ($user_id) {
                $postguest = $datauser['postguest'] + 1;
                $db->exec("UPDATE `users` SET `postguest` = '$postguest', `lastpost` = '" . time() . "' WHERE `id` = '$user_id'");
            }
            header('location: index.php'); exit;
        } else {
            echo functions::display_error($error, '<a href="index.php">' . $lng['back'] . '</a>');
        }
        break;

    case 'otvet':
        /*
        -----------------------------------------------------------------
        Добавление "ответа Админа"
        -----------------------------------------------------------------
        */
        if ($rights >= 6 && $id) {
            if (isset($_POST['submit'])
                && isset($_POST['token'])
                && isset($_SESSION['token'])
                && $_POST['token'] == $_SESSION['token']
            ) {
                $reply = isset($_POST['otv']) ? functions::checkin(mb_substr(trim($_POST['otv']), 0, 5000)) : '';
                $stmt = $db->prepare("UPDATE `guest` SET
                    `admin` = ?,
                    `otvet` = ?,
                    `otime` = '" . time() . "'
                    WHERE `id` = '$id' LIMIT 1
                ");
                $stmt->execute([
                    $login,
                    $reply
                ]);
                header("location: index.php"); exit;
            } else {
                echo '<div class="phdr"><a href="index.php"><b>' . $lng['guestbook'] . '</b></a> | ' . $lng['reply'] . '</div>';
                $res = $db->query("SELECT * FROM `guest` WHERE `id` = '$id' LIMIT 1")->fetch();
                $token = mt_rand(1000, 100000);
                $_SESSION['token'] = $token;
                echo '<div class="menu">' .
                    '<div class="quote"><b>' . $res['name'] . '</b>' .
                    '<br />' . functions::checkout($res['text']) . '</div>' .
                    '<form name="form" action="index.php?act=otvet&amp;id=' . $id . '" method="post">' .
                    '<p><h3>' . $lng['reply'] . '</h3>' . bbcode::auto_bb('form', 'otv') .
                    '<textarea rows="' . $set_user['field_h'] . '" name="otv">' . functions::checkout($res['otvet']) . '</textarea></p>' .
                    '<p><input type="submit" name="submit" value="' . $lng['reply'] . '"/></p>' .
                    '<input type="hidden" name="token" value="' . $token . '"/>' .
                    '</form></div>' .
                    '<div class="phdr"><a href="index.php">' . $lng['back'] . '</a></div>';
            }
        }
        break;

    case 'edit':
        /*
        -----------------------------------------------------------------
        Редактирование поста
        -----------------------------------------------------------------
        */
        if ($rights >= 6 && $id) {
            if (isset($_POST['submit'])
                && isset($_POST['token'])
                && isset($_SESSION['token'])
                && $_POST['token'] == $_SESSION['token']
            ) {
                $res = $db->query("SELECT `edit_count` FROM `guest` WHERE `id`='$id' LIMIT 1")->fetch();
                $edit_count = $res['edit_count'] + 1;
                $msg = isset($_POST['msg']) ? functions::checkin(mb_substr(trim($_POST['msg']), 0, 5000)) : '';
                $stmt = $db->prepare("UPDATE `guest` SET
                    `text` = ?,
                    `edit_who` = ?,
                    `edit_time` = '" . time() . "',
                    `edit_count` = '$edit_count'
                    WHERE `id` = '$id' LIMIT 1
                ");
                $stmt->execute([
                    $msg,
                    $login
                ]);
                header("location: index.php"); exit;
            } else {
                $token = mt_rand(1000, 100000);
                $_SESSION['token'] = $token;
                $res = $db->query("SELECT * FROM `guest` WHERE `id` = '$id' LIMIT 1")->fetch();
                $text = htmlentities($res['text'], ENT_QUOTES, 'UTF-8');
                echo '<div class="phdr"><a href="index.php"><b>' . $lng['guestbook'] . '</b></a> | ' . $lng['edit'] . '</div>' .
                    '<div class="rmenu">' .
                    '<form name="form" action="index.php?act=edit&amp;id=' . $id . '" method="post">' .
                    '<p><b>' . $lng['author'] . ':</b> ' . $res['name'] . '</p><p>';
                echo bbcode::auto_bb('form', 'msg');
                echo '<textarea rows="' . $set_user['field_h'] . '" name="msg">' . $text . '</textarea></p>' .
                    '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p>' .
                    '<input type="hidden" name="token" value="' . $token . '"/>' .
                    '</form></div>' .
                    '<div class="phdr"><a href="index.php">' . $lng['back'] . '</a></div>';
            }
        }
        break;

    case 'clean':
        /*
        -----------------------------------------------------------------
        Очистка Гостевой
        -----------------------------------------------------------------
        */
        if ($rights >= 7) {
            if (isset($_POST['submit'])) {
                // Проводим очистку Гостевой, согласно заданным параметрам
                $adm = isset($_SESSION['ga']) ? 1 : 0;
                $cl = isset($_POST['cl']) ? intval($_POST['cl']) : '';
                switch ($cl) {
                    case '1':
                        // Чистим сообщения, старше 1 дня
                        $db->exec("DELETE FROM `guest` WHERE `adm`='$adm' AND `time` < '" . (time() - 86400) . "'");
                        echo '<p>' . $lng['clear_day_ok'] . '</p>';
                        break;

                    case '2':
                        // Проводим полную очистку
                        $db->exec("DELETE FROM `guest` WHERE `adm`='$adm'");
                        echo '<p>' . $lng['clear_full_ok'] . '</p>';
                        break;
                    default :
                        // Чистим сообщения, старше 1 недели
                        $db->exec("DELETE FROM `guest` WHERE `adm`='$adm' AND `time`<='" . (time() - 604800) . "';");
                        echo '<p>' . $lng['clear_week_ok'] . '</p>';
                }
                $db->query("OPTIMIZE TABLE `guest`");
                echo '<p><a href="index.php">' . $lng['guestbook'] . '</a></p>';
            } else {
                // Запрос параметров очистки
                echo '<div class="phdr"><a href="index.php"><b>' . $lng['guestbook'] . '</b></a> | ' . $lng['clear'] . '</div>' .
                    '<div class="menu">' .
                    '<form id="clean" method="post" action="index.php?act=clean">' .
                    '<p><h3>' . $lng['clear_param'] . '</h3>' .
                    '<input type="radio" name="cl" value="0" checked="checked" />' . $lng['clear_param_week'] . '<br />' .
                    '<input type="radio" name="cl" value="1" />' . $lng['clear_param_day'] . '<br />' .
                    '<input type="radio" name="cl" value="2" />' . $lng['clear_param_all'] . '</p>' .
                    '<p><input type="submit" name="submit" value="' . $lng['clear'] . '" /></p>' .
                    '</form></div>' .
                    '<div class="phdr"><a href="index.php">' . $lng['cancel'] . '</a></div>';
            }
        }
        break;

    case 'ga':
        /*
        -----------------------------------------------------------------
        Переключение режима работы Гостевая / Админ-клуб
        -----------------------------------------------------------------
        */
        if ($rights >= 1) {
            if (isset($_GET['do']) && $_GET['do'] == 'set') {
                $_SESSION['ga'] = 1;
            } else {
                unset($_SESSION['ga']);
            }
        }

    default:
        /*
        -----------------------------------------------------------------
        Отображаем Гостевую, или Админ клуб
        -----------------------------------------------------------------
        */
        if (!$set['mod_guest'])
            echo '<div class="alarm">' . $lng['guestbook_closed'] . '</div>';
        echo '<div class="phdr"><b>' . $lng['guestbook'] . '</b></div>';
        if ($rights > 0) {
            $menu = array();
            $menu[] = isset($_SESSION['ga']) ? '<a href="index.php?act=ga">' . $lng['guestbook'] . '</a>' : '<b>' . $lng['guestbook'] . '</b>';
            $menu[] = isset($_SESSION['ga']) ? '<b>' . $lng['admin_club'] . '</b>' : '<a href="index.php?act=ga&amp;do=set">' . $lng['admin_club'] . '</a>';
            if ($rights >= 7)
                $menu[] = '<a href="index.php?act=clean">' . $lng['clear'] . '</a>';
            echo '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
        }
        // Форма ввода нового сообщения
        if (($user_id || $set['mod_guest'] == 2) && !isset($ban['1']) && !isset($ban['13'])) {
            $token = mt_rand(1000, 100000);
            $_SESSION['token'] = $token;
            echo '<div class="gmenu"><form name="form" action="index.php?act=say" method="post">';
            if (!$user_id)
                echo $lng['name'] . ' (max 25):<br/><input type="text" name="name" maxlength="25"/><br/>';
            echo '<b>' . $lng['message'] . '</b> <small>(max 5000)</small>:<br/>';
            echo bbcode::auto_bb('form', 'msg');
            echo '<textarea rows="' . $set_user['field_h'] . '" name="msg"></textarea><br/>';
            if ($set_user['translit'])
                echo '<input type="checkbox" name="msgtrans" value="1" />&nbsp;' . $lng['translit'] . '<br/>';
            if (!$user_id) {
                // CAPTCHA для гостей
                echo '<img src="../captcha.php?r=' . rand(1000, 9999) . '" alt="' . $lng['captcha'] . '"/><br />' .
                    '<input type="text" size="5" maxlength="5"  name="code"/>&#160;' . $lng['captcha'] . '<br />';
            }
            echo '<input type="hidden" name="token" value="' . $token . '"/>' .
                '<input type="submit" name="submit" value="' . $lng['sent'] . '"/></form></div>';
        } else {
            echo '<div class="rmenu">' . $lng['access_guest_forbidden'] . '</div>';
        }
        $total = $db->query("SELECT COUNT(*) FROM `guest` WHERE `adm`='" . (isset($_SESSION['ga']) ? 1 : 0) . "'")->fetchColumn();
        echo '<div class="phdr"><b>' . $lng['comments'] . '</b></div>';
        if ($total > $kmess) echo '<div class="topmenu">' . functions::display_pagination('index.php?', $start, $total, $kmess) . '</div>';
        if ($total) {
            if (isset($_SESSION['ga']) && $rights >= "1") {
                // Запрос для Админ клуба
                echo '<div class="rmenu"><b>Admin Club</b></div>';
                $stmt = $db->query("SELECT `guest`.*, `guest`.`id` AS `gid`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
                FROM `guest` LEFT JOIN `users` ON `guest`.`user_id` = `users`.`id`
                WHERE `guest`.`adm`='1' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
            } else {
                // Запрос для обычной Гастивухи
                $stmt = $db->query("SELECT `guest`.*, `guest`.`id` AS `gid`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
                FROM `guest` LEFT JOIN `users` ON `guest`.`user_id` = `users`.`id`
                WHERE `guest`.`adm`='0' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
            }
            $i = 0;
            while ($res = $stmt->fetch()) {
                $text = '';
                echo ++$i % 2 ? '<div class="list2">' : '<div class="list1">';
                if (!$res['id']) {
                    // Запрос по гостям
                    $res_g = $db->query("SELECT `lastdate` FROM `cms_sessions` WHERE `session_id` = '" . md5($res['ip'] . $res['browser']) . "' LIMIT 1")->fetch();
                    $res['lastdate'] = $res_g['lastdate'];
                }
                // Время создания поста
                $text = ' <span class="gray">(' . functions::display_date($res['time']) . ')</span>';
                if ($res['user_id']) {
                    // Для зарегистрированных показываем ссылки и смайлы
                    $post = functions::checkout($res['text'], 1, 1);
                    if ($set_user['smileys'])
                        $post = functions::smileys($post, $res['rights'] >= 1 ? 1 : 0);
                } else {
                    // Для гостей обрабатываем имя и фильтруем ссылки
                    $res['name'] = functions::checkout($res['name']);
                    $post = functions::antilink(functions::checkout($res['text'], 0, 2));
                }
                if ($res['edit_count']) {
                    // Если пост редактировался, показываем кем и когда
                    $post .= '<br /><span class="gray"><small>Изм. <b>' . $res['edit_who'] . '</b> (' . functions::display_date($res['edit_time']) . ') <b>[' . $res['edit_count'] . ']</b></small></span>';
                }
                if (!empty($res['otvet'])) {
                    // Ответ Администрации
                    $otvet = functions::checkout($res['otvet'], 1, 1);
                    if ($set_user['smileys'])
                        $otvet = functions::smileys($otvet, 1);
                    $post .= '<div class="reply"><b>' . $res['admin'] . '</b>: (' . functions::display_date($res['otime']) . ')<br/>' . $otvet . '</div>';
                }
                if ($rights >= 6) {
                    $subtext = '<a href="index.php?act=otvet&amp;id=' . $res['gid'] . '">' . $lng['reply'] . '</a>' .
                        ($rights >= $res['rights'] ? ' | <a href="index.php?act=edit&amp;id=' . $res['gid'] . '">' . $lng['edit'] . '</a> | <a href="index.php?act=delpost&amp;id=' . $res['gid'] . '">' . $lng['delete'] . '</a>' : '');
                } else {
                    $subtext = '';
                }
                $arg = array(
                    'header' => $text,
                    'body'   => $post,
                    'sub'    => $subtext
                );
                echo functions::display_user($res, $arg);
                echo '</div>';
            }
        } else {
            echo '<div class="menu"><p>' . $lng['guestbook_empty'] . '</p></div>';
        }
        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('index.php?', $start, $total, $kmess) . '</div>' .
                '<p><form action="index.php" method="get"><input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
        }
        break;
}

require('../incfiles/end.php');