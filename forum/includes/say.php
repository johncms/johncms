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

/*
-----------------------------------------------------------------
Закрываем доступ для определенных ситуаций
-----------------------------------------------------------------
*/
if (!$id || !$user_id || isset($ban['1']) || isset($ban['11']) || (!core::$user_rights && $set['mod_forum'] == 3)) {
    require('../incfiles/head.php');
    echo functions::display_error($lng['access_forbidden']);
    require('../incfiles/end.php');
    exit;
}

/*
-----------------------------------------------------------------
Вспомогательная Функция обработки ссылок форума
-----------------------------------------------------------------
*/
function forum_link($m)
{
    global $set;
    if (!isset($m[3])) {
        return '[url=' . $m[1] . ']' . $m[2] . '[/url]';
    } else {
        $p = parse_url($m[3]);
        if ('http://' . $p['host'] . (isset($p['path']) ? $p['path'] : '') . '?id=' == $set['homeurl'] . '/forum/index.php?id=') {
            $thid = abs(intval(preg_replace('/(.*?)id=/si', '', $m[3])));
            $stmt = core::$db->query("SELECT `text` FROM `forum` WHERE `id`= '$thid' AND `type` = 't' AND `close` != '1' LIMIT 1");
            if ($stmt->rowCount()) {
                $res = $stmt->fetch();
                $name = $res['text'];
                if (mb_strlen($name) > 40) {
                    $name = mb_substr($name, 0, 40) . '...';
                }

                return '[url=' . $m[3] . ']' . $name . '[/url]';
            } else {
                return $m[3];
            }
        } else
            return $m[3];
    }
}

// Проверка на флуд
$flood = functions::antiflood();
if ($flood) {
    require('../incfiles/head.php');
    echo functions::display_error($lng['error_flood'] . ' ' . $flood . $lng['sec'], '<a href="index.php?id=' . $id . '&amp;start=' . $start . '">' . $lng['back'] . '</a>');
    require('../incfiles/end.php');
    exit;
}

$headmod = 'forum,' . $id . ',1';
$agn1 = strtok($agn, ' ');
$type1 = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' LIMIT 1")->fetch();
switch ($type1['type']) {
    case 't':
        /*
        -----------------------------------------------------------------
        Добавление простого сообщения
        -----------------------------------------------------------------
        */
        if (($type1['edit'] == 1 || $type1['close'] == 1) && $rights < 7) {
            // Проверка, закрыта ли тема
            require('../incfiles/head.php');
            echo functions::display_error($lng_forum['error_topic_closed'], '<a href="index.php?id=' . $id . '">' . $lng['back'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        $msg = isset($_POST['msg']) ? functions::checkin(trim($_POST['msg'])) : '';
        if (isset($_POST['msgtrans']))
            $msg = functions::trans($msg);
        //Обрабатываем ссылки
        $msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', 'forum_link', $msg);
        if (isset($_POST['submit'])
            && !empty($_POST['msg'])
            && isset($_POST['token'])
            && isset($_SESSION['token'])
            && $_POST['token'] == $_SESSION['token']
        ) {
            // Проверяем на минимальную длину
            if (mb_strlen($msg) < 4) {
                require('../incfiles/head.php');
                echo functions::display_error($lng['error_message_short'], '<a href="index.php?id=' . $id . '">' . $lng['back'] . '</a>');
                require('../incfiles/end.php');
                exit;
            }
            // Проверяем, не повторяется ли сообщение?
            $stmt = $db->query("SELECT * FROM `forum` WHERE `user_id` = '$user_id' AND `type` = 'm' ORDER BY `time` DESC");
            if ($stmt->rowCount()) {
                $res = $stmt->fetch();
                if ($msg == $res['text']) {
                    require('../incfiles/head.php');
                    echo functions::display_error($lng['error_message_exists'], '<a href="index.php?id=' . $id . '&amp;start=' . $start . '">' . $lng['back'] . '</a>');
                    require('../incfiles/end.php');
                    exit;
                }
            }
            // Удаляем фильтр, если он был
            if (isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $id) {
                unset($_SESSION['fsort_id']);
                unset($_SESSION['fsort_users']);
            }

            unset($_SESSION['token']);

            // Добавляем сообщение в базу
            $stmt = $db->prepare("INSERT INTO `forum` SET
                `refid` = '$id',
                `type` = 'm' ,
                `time` = '" . time() . "',
                `user_id` = '$user_id',
                `from` = ?,
                `ip` = '" . core::$ip . "',
                `ip_via_proxy` = '" . core::$ip_via_proxy . "',
                `soft` = ?,
                `text` = ?,
                `edit` = '',
                `curators` = ''
            ");
            $stmt->execute([
                $login,
                $agn1,
                $msg
            ]);
            $fadd = $db->lastInsertId();
            // Обновляем время топика
            $db->exec("UPDATE `forum` SET
                `time` = '" . time() . "'
                WHERE `id` = '$id'
            ");
            // Обновляем статистику юзера
            $db->exec("UPDATE `users` SET
                `postforum`='" . ($datauser['postforum'] + 1) . "',
                `lastpost` = '" . time() . "'
                WHERE `id` = '$user_id'
            ");
            // Вычисляем, на какую страницу попадает добавляемый пост
            $page = $set_forum['upfp'] ? 1 : ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `refid` = '$id'" . ($rights >= 7 ? '' : " AND `close` != '1'"))->fetchColumn() / $kmess);
            if (isset($_POST['addfiles'])) {
                header("Location: index.php?id=$fadd&act=addfile"); exit;
            } else {
                header("Location: index.php?id=$id&page=$page"); exit;
            }
        } else {
            require('../incfiles/head.php');
            if ($datauser['postforum'] == 0) {
                if (!isset($_GET['yes'])) {
                    $lng_faq = core::load_lng('faq');
                    echo '<p>' . $lng_faq['forum_rules_text'] . '</p>' .
                        '<p><a href="index.php?act=say&amp;id=' . $id . '&amp;yes">' . $lng_forum['agree'] . '</a> | ' .
                        '<a href="index.php?id=' . $id . '">' . $lng_forum['not_agree'] . '</a></p>';
                    require('../incfiles/end.php');
                    exit;
                }
            }
            $msg_pre = functions::checkout($msg, 1, 1);
            if ($set_user['smileys']) {
                $msg_pre = functions::smileys($msg_pre, $datauser['rights'] ? 1 : 0);
            }
            $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);
            echo '<div class="phdr"><b>' . $lng_forum['topic'] . ':</b> ' . _e($type1['text']) . '</div>';
            if ($msg && !isset($_POST['submit'])) {
                echo '<div class="list1">' . functions::display_user($datauser, array('iphide' => 1, 'header' => '<span class="gray">(' . functions::display_date(time()) . ')</span>', 'body' => $msg_pre)) . '</div>';
            }
            echo '<form name="form" action="index.php?act=say&amp;id=' . $id . '&amp;start=' . $start . '" method="post"><div class="gmenu">' .
                '<p><h3>' . $lng_forum['post'] . '</h3>';
            echo '</p><p>' . bbcode::auto_bb('form', 'msg');
            echo '<textarea rows="' . $set_user['field_h'] . '" name="msg">' . (empty($_POST['msg']) ? '' : functions::checkout($msg)) . '</textarea></p>' .
                '<p><input type="checkbox" name="addfiles" value="1" ' . (isset($_POST['addfiles']) ? 'checked="checked" ' : '') . '/> ' . $lng_forum['add_file'];
            if ($set_user['translit']) {
                echo '<br /><input type="checkbox" name="msgtrans" value="1" ' . (isset($_POST['msgtrans']) ? 'checked="checked" ' : '') . '/> ' . $lng['translit'];
            }
            $token = mt_rand(1000, 100000);
            $_SESSION['token'] = $token;
            echo '</p><p>' .
                '<input type="submit" name="submit" value="' . $lng['sent'] . '" style="width: 107px; cursor: pointer"/> ' .
                ($set_forum['preview'] ? '<input type="submit" value="' . $lng['preview'] . '" style="width: 107px; cursor: pointer"/>' : '') .
                '<input type="hidden" name="token" value="' . $token . '"/>' .
                '</p></div></form>';
        }

        echo '<div class="phdr"><a href="../pages/faq.php?act=trans">' . $lng['translit'] . '</a> | ' .
            '<a href="../pages/faq.php?act=smileys">' . $lng['smileys'] . '</a></div>' .
            '<p><a href="index.php?id=' . $id . '&amp;start=' . $start . '">' . $lng['back'] . '</a></p>';
        break;

    case 'm':
        /*
        -----------------------------------------------------------------
        Добавление сообщения с цитированием поста
        -----------------------------------------------------------------
        */
        $th = $type1['refid'];
        $th1 = $db->query("SELECT * FROM `forum` WHERE `id` = '$th' LIMIT 1")->fetch();
        if (($th1['edit'] == 1 || $th1['close'] == 1) && $rights < 7) {
            require('../incfiles/head.php');
            echo functions::display_error($lng_forum['error_topic_closed'], '<a href="index.php?id=' . $th1['id'] . '">' . $lng['back'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        if ($type1['user_id'] == $user_id) {
            require('../incfiles/head.php');
            echo functions::display_error('Нельзя отвечать на свое же сообщение', '<a href="index.php?id=' . $th1['id'] . '">' . $lng['back'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        $shift = (core::$system_set['timeshift'] + core::$user_set['timeshift']) * 3600;
        $vr = date("d.m.Y / H:i", $type1['time'] + $shift);
        $msg = isset($_POST['msg']) ? functions::checkin(trim($_POST['msg'])) : '';
        $txt = isset($_POST['txt']) ? intval($_POST['txt']) : FALSE;
        if (isset($_POST['msgtrans'])) {
            $msg = functions::trans($msg);
        }
        if (!empty($_POST['citata'])) {
            // Если была цитата, форматируем ее и обрабатываем
            $citata = isset($_POST['citata']) ? trim($_POST['citata']) : '';
            $citata = bbcode::notags($citata);
            $citata = preg_replace('#\[c\](.*?)\[/c\]#si', '', $citata);
            $citata = mb_substr($citata, 0, 200);
            $tp = date("d.m.Y H:i", $type1['time']);
            $msg = '[c][url=' . $home . '/forum/index.php?act=post&id=' . $type1['id'] . ']#[/url] ' . $type1['from'] . ' ([time]' . $tp . "[/time])\n" . $citata . '[/c]' . $msg;
        } elseif (isset($_POST['txt'])) {
            // Если был ответ, обрабатываем реплику
            switch ($txt) {
                case 2:
                    $repl = $type1['from'] . ', ' . $lng_forum['reply_1'] . ', ';
                    break;

                case 3:
                    $repl = $type1['from'] . ', ' . $lng_forum['reply_2'] . ' ([url=' . $set['homeurl'] . '/forum/index.php?act=post&id=' . $type1['id'] . ']' . $vr . '[/url]) ' . $lng_forum['reply_3'] . ', ';
                    break;

                case 4:
                    $repl = $type1['from'] . ', ' . $lng_forum['reply_4'] . ' ';
                    break;

                default :
                    $repl = $type1['from'] . ', ';
            }
            $msg = $repl . ' ' . $msg;
        }
        //Обрабатываем ссылки
        $msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', 'forum_link', $msg);
        if (isset($_POST['submit'])
            && isset($_POST['token'])
            && isset($_SESSION['token'])
            && $_POST['token'] == $_SESSION['token']
        ) {
            if (empty($_POST['msg'])) {
                require('../incfiles/head.php');
                echo functions::display_error($lng['error_empty_message'], '<a href="index.php?act=say&amp;id=' . $th . (isset($_GET['cyt']) ? '&amp;cyt' : '') . '">' . $lng['repeat'] . '</a>');
                require('../incfiles/end.php');
                exit;
            }
            // Проверяем на минимальную длину
            if (mb_strlen($msg) < 4) {
                require('../incfiles/head.php');
                echo functions::display_error($lng['error_message_short'], '<a href="index.php?id=' . $id . '">' . $lng['back'] . '</a>');
                require('../incfiles/end.php');
                exit;
            }
            // Проверяем, не повторяется ли сообщение?
            $stmt = $db->query("SELECT * FROM `forum` WHERE `user_id` = '$user_id' AND `type` = 'm' ORDER BY `time` DESC LIMIT 1");
            if ($stmt->rowCount()) {
                $res = $stmt->fetch();
                if ($msg == $res['text']) {
                    require('../incfiles/head.php');
                    echo functions::display_error($lng['error_message_exists'], '<a href="index.php?id=' . $th . '&amp;start=' . $start . '">' . $lng['back'] . '</a>');
                    require('../incfiles/end.php');
                    exit;
                }
            }
            // Удаляем фильтр, если он был
            if (isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $th) {
                unset($_SESSION['fsort_id']);
                unset($_SESSION['fsort_users']);
            }

            unset($_SESSION['token']);

            // Добавляем сообщение в базу
            $stmt = $db->prepare("INSERT INTO `forum` SET
                `refid` = '$th',
                `type` = 'm',
                `time` = '" . time() . "',
                `user_id` = '$user_id',
                `from` = ?,
                `ip` = '" . core::$ip . "',
                `ip_via_proxy` = '" . core::$ip_via_proxy . "',
                `soft` = ?,
                `text` = ?,
                `edit` = '',
                `curators` = ''
            ");
            $stmt->execute([
                $login,
                $agn1,
                $msg
            ]);
            $fadd = $db->lastInsertId();
            // Обновляем время топика
            $db->exec("UPDATE `forum`
                SET `time` = '" . time() . "'
                WHERE `id` = '$th'
            ");
            // Обновляем статистику юзера
            $db->exec("UPDATE `users` SET
                `postforum`='" . ($datauser['postforum'] + 1) . "',
                `lastpost` = '" . time() . "'
                WHERE `id` = '$user_id'
            ");
            // Вычисляем, на какую страницу попадает добавляемый пост
            $page = $set_forum['upfp'] ? 1 : ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `refid` = '$th'" . ($rights >= 7 ? '' : " AND `close` != '1'"))->fetchColumn() / $kmess);
            if (isset($_POST['addfiles'])) {
                header("Location: index.php?id=$fadd&act=addfile"); exit;
            } else {
                header("Location: index.php?id=$th&page=$page"); exit;
            }
        } else {
            $textl = $lng['forum'];
            require('../incfiles/head.php');
            if ($datauser['postforum'] == 0) {
                if (!isset($_GET['yes'])) {
                    $lng_faq = core::load_lng('faq');
                    echo '<p>' . $lng_faq['forum_rules_text'] . '</p>';
                    echo '<p><a href="index.php?act=say&amp;id=' . $id . '&amp;yes&amp;cyt">' . $lng_forum['agree'] . '</a> | <a href="index.php?id=' . $type1['refid'] . '">' . $lng_forum['not_agree'] . '</a></p>';
                    require('../incfiles/end.php');
                    exit;
                }
            }
            $msg_pre = functions::checkout($msg, 1, 1);
            if ($set_user['smileys']) {
                $msg_pre = functions::smileys($msg_pre, $datauser['rights'] ? 1 : 0);
            }
            $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);
            echo '<div class="phdr"><b>' . $lng_forum['topic'] . ':</b> ' . _e($th1['text']) . '</div>';
            $qt = trim(preg_replace('#\[c\](.*?)\[/c\]#si', '', $type1['text']));
            $qt = functions::checkout($qt, 0, 2);
            if (!empty($_POST['msg']) && !isset($_POST['submit'])) {
                echo '<div class="list1">' . functions::display_user($datauser, array('iphide' => 1, 'header' => '<span class="gray">(' . functions::display_date(time()) . ')</span>', 'body' => $msg_pre)) . '</div>';
            }
            echo '<form name="form" action="index.php?act=say&amp;id=' . $id . '&amp;start=' . $start . (isset($_GET['cyt']) ? '&amp;cyt' : '') . '" method="post"><div class="gmenu">';
            if (isset($_GET['cyt'])) {
                // Форма с цитатой
                echo '<p><b>' . $type1['from'] . '</b> <span class="gray">(' . $vr . ')</span></p>' .
                    '<p><h3>' . $lng_forum['cytate'] . '</h3>' .
                    '<textarea rows="' . $set_user['field_h'] . '" name="citata">' . (empty($_POST['citata']) ? $qt : functions::checkout($_POST['citata'])) . '</textarea>' .
                    '<br /><small>' . $lng_forum['cytate_help'] . '</small></p>';
            } else {
                // Форма с репликой
                echo '<p><h3>' . $lng_forum['reference'] . '</h3>' .
                    '<input type="radio" value="0" ' . (!$txt ? 'checked="checked"' : '') . ' name="txt" />&#160;<b>' . $type1['from'] . '</b>,<br />' .
                    '<input type="radio" value="2" ' . ($txt == 2 ? 'checked="checked"' : '') . ' name="txt" />&#160;<b>' . $type1['from'] . '</b>, ' . $lng_forum['reply_1'] . ',<br />' .
                    '<input type="radio" value="3" ' . ($txt == 3 ? 'checked="checked"'
                        : '') . ' name="txt" />&#160;<b>' . $type1['from'] . '</b>, ' . $lng_forum['reply_2'] . ' (<a href="index.php?act=post&amp;id=' . $type1['id'] . '">' . $vr . '</a>) ' . $lng_forum['reply_3'] . ',<br />' .
                    '<input type="radio" value="4" ' . ($txt == 4 ? 'checked="checked"' : '') . ' name="txt" />&#160;<b>' . $type1['from'] . '</b>, ' . $lng_forum['reply_4'] . '</p>';
            }
            echo '<p><h3>' . $lng_forum['post'] . '</h3>';
            echo '</p><p>' . bbcode::auto_bb('form', 'msg');
            echo '<textarea rows="' . $set_user['field_h'] . '" name="msg">' . (empty($_POST['msg']) ? '' : functions::checkout($_POST['msg'])) . '</textarea></p>' .
                '<p><input type="checkbox" name="addfiles" value="1" ' . (isset($_POST['addfiles']) ? 'checked="checked" ' : '') . '/> ' . $lng_forum['add_file'];
            if ($set_user['translit']) {
                echo '<br /><input type="checkbox" name="msgtrans" value="1" ' . (isset($_POST['msgtrans']) ? 'checked="checked" ' : '') . '/> ' . $lng['translit'];
            }
            $token = mt_rand(1000, 100000);
            $_SESSION['token'] = $token;
            echo '</p><p><input type="submit" name="submit" value="' . $lng['sent'] . '" style="width: 107px; cursor: pointer;"/> ' .
                ($set_forum['preview'] ? '<input type="submit" value="' . $lng['preview'] . '" style="width: 107px; cursor: pointer;"/>' : '') .
                '<input type="hidden" name="token" value="' . $token . '"/>' .
                '</p></div></form>';
        }
        echo '<div class="phdr"><a href="../pages/faq.php?act=trans">' . $lng['translit'] . '</a> | ' .
            '<a href="../pages/faq.php?act=smileys">' . $lng['smileys'] . '</a></div>' .
            '<p><a href="index.php?id=' . $type1['refid'] . '&amp;start=' . $start . '">' . $lng['back'] . '</a></p>';
        break;

    default:
        require('../incfiles/head.php');
        echo functions::display_error($lng_forum['error_topic_deleted'], '<a href="index.php">' . $lng['to_forum'] . '</a>');
        require('../incfiles/end.php');
}