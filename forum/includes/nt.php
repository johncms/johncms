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
    echo functions::display_error($lng['error_flood'] . ' ' . $flood . $lng['sec'] . ', <a href="index.php?id=' . $id . '&amp;start=' . $start . '">' . $lng['back'] . '</a>');
    require('../incfiles/end.php');
    exit;
}

$stmt = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 'r' LIMIT 1");
if (!$stmt->rowCount()) {
    require('../incfiles/head.php');
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}
$res_r = $stmt->fetch();

$th = isset($_POST['th']) ? trim(mb_substr(functions::checkin($_POST['th'], 1), 0, 255)) : '';
$msg = isset($_POST['msg']) ? functions::checkin($_POST['msg']) : '';

if (isset($_POST['msgtrans'])) {
    $th = functions::trans($th);
    $msg = functions::trans($msg);
}

$msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', 'forum_link', $msg);

if (isset($_POST['submit'])
    && isset($_POST['token'])
    && isset($_SESSION['token'])
    && $_POST['token'] == $_SESSION['token']
) {
    $error = array();
    if (empty($th)) {
        $error[] = $lng_forum['error_topic_name'];
    }
    if (mb_strlen($th) < 2) {
        $error[] = $lng_forum['error_topic_name_lenght'];
    }
    if (empty($msg)) {
        $error[] = $lng['error_empty_message'];
    }
    if (mb_strlen($msg) < 4) {
        $error[] = $lng['error_message_short'];
    }
    if (!$error) {
        $msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', 'forum_link', $msg);
        // Прверяем, есть ли уже такая тема в текущем разделе?
        $stmt = $db->prepare("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `refid` = '$id' AND `text` = ?");
        $stmt->execute([
            $th
        ]);
        if ($stmt->fetchColumn()) {
            $error[] = $lng_forum['error_topic_exists'];
        }
        // Проверяем, не повторяется ли сообщение?
        $stmt = $db->query("SELECT `text` FROM `forum` WHERE `user_id` = '$user_id' AND `type` = 'm' ORDER BY `time` DESC LIMIT 1");
        if ($stmt->rowCount()) {
            $res = $stmt->fetch();
            if ($msg == $res['text']) {
                $error[] = $lng['error_message_exists'];
            }
        }
    }
    if (!$error) {
        unset($_SESSION['token']);

        // Если задано в настройках, то назначаем топикстартера куратором
        $curator = $res_r['edit'] == 1 ? serialize(array($user_id => $login)) : '';

        // Добавляем тему
        $stmt = $db->prepare("INSERT INTO `forum` SET
            `refid` = '$id',
            `type` = 't',
            `time` = '" . time() . "',
            `user_id` = '$user_id',
            `from` = ?,
            `text` = ?,
            `soft` = '',
            `edit` = '',
            `curators` = ?
        ");
        $stmt->execute([
            $login,
            $th,
            $curator
        ]);
        $rid = $db->lastInsertId();

        // Добавляем текст поста
        $stmt = $db->prepare("INSERT INTO `forum` SET
            `refid` = '$rid',
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
            $agn,
            $msg
        ]);
        $postid = $db->lastInsertId();

        // Записываем счетчик постов юзера
        $fpst = $datauser['postforum'] + 1;
        $db->exec("UPDATE `users` SET
            `postforum` = '$fpst',
            `lastpost` = '" . time() . "'
            WHERE `id` = '$user_id'
        ");

        // Ставим метку о прочтении
        $db->exec("INSERT INTO `cms_forum_rdm` SET
            `topic_id`='$rid',
            `user_id`='$user_id',
            `time`='" . time() . "'
        ");

        if (isset($_POST['addfiles'])) {
            header("Location: index.php?id=$postid&act=addfile"); exit;
        } else {
            header("Location: index.php?id=$rid"); exit;
        }
    } else {
        // Выводим сообщение об ошибке
        require('../incfiles/head.php');
        echo functions::display_error($error, '<a href="index.php?act=nt&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
        require('../incfiles/end.php');
        exit;
    }
} else {
    $res_c = $db->query("SELECT * FROM `forum` WHERE `id` = '" . $res_r['refid'] . "' LIMIT 1")->fetch();
    require('../incfiles/head.php');
    if ($datauser['postforum'] == 0) {
        if (!isset($_GET['yes'])) {
            $lng_faq = core::load_lng('faq');
            echo '<p>' . $lng_faq['forum_rules_text'] . '</p>';
            echo '<p><a href="index.php?act=nt&amp;id=' . $id . '&amp;yes">' . $lng_forum['agree'] . '</a> | <a href="index.php?id=' . $id . '">' . $lng_forum['not_agree'] . '</a></p>';
            require('../incfiles/end.php');
            exit;
        }
    }
    $msg_pre = functions::checkout($msg, 1, 1);
    if ($set_user['smileys']) {
        $msg_pre = functions::smileys($msg_pre, $datauser['rights'] ? 1 : 0);
    }
    $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);
    echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['new_topic'] . '</div>';
    if ($msg && $th && !isset($_POST['submit'])) {
        echo '<div class="list1">' . functions::image('op.gif') . '<span style="font-weight: bold">' . _e($th) . '</span></div>' .
            '<div class="list2">' . functions::display_user($datauser, array('iphide' => 1, 'header' => '<span class="gray">(' . functions::display_date(time()) . ')</span>', 'body' => $msg_pre)) . '</div>';
    }
    echo '<form name="form" action="index.php?act=nt&amp;id=' . $id . (isset($_GET['yes']) ? '&yes' : '') . '" method="post">' .
        '<div class="gmenu">' .
        '<p><h3>' . $lng['section'] . '</h3>' .
        '<a href="index.php?id=' . $res_c['id'] . '">' . _e($res_c['text']) . '</a> | <a href="index.php?id=' . $res_r['id'] . '">' . _e($res_r['text']) . '</a></p>' .
        '<p><h3>' . $lng_forum['new_topic_name'] . '</h3>' .
        '<input type="text" size="20" maxlength="255" name="th" value="' . _e($th) . '"/></p>' .
        '<p><h3>' . $lng_forum['post'] . '</h3>';
    echo '</p><p>' . bbcode::auto_bb('form', 'msg');
    echo '<textarea rows="' . $set_user['field_h'] . '" name="msg">' . (isset($_POST['msg']) ? functions::checkout($_POST['msg']) : '') . '</textarea></p>' .
        '<p><input type="checkbox" name="addfiles" value="1" ' . (isset($_POST['addfiles']) ? 'checked="checked" ' : '') . '/> ' . $lng_forum['add_file'];
    if ($set_user['translit']) {
        echo '<br /><input type="checkbox" name="msgtrans" value="1" ' . (isset($_POST['msgtrans']) ? 'checked="checked" ' : '') . '/> ' . $lng['translit'];
    }
    $token = mt_rand(1000, 100000);
    $_SESSION['token'] = $token;
    echo '</p><p><input type="submit" name="submit" value="' . $lng['save'] . '" style="width: 107px; cursor: pointer;"/> ' .
        ($set_forum['preview'] ? '<input type="submit" value="' . $lng['preview'] . '" style="width: 107px; cursor: pointer;"/>' : '') .
        '<input type="hidden" name="token" value="' . $token . '"/>' .
        '</p></div></form>' .
        '<div class="phdr"><a href="../pages/faq.php?act=trans">' . $lng['translit'] . '</a> | ' .
        '<a href="../pages/faq.php?act=smileys">' . $lng['smileys'] . '</a></div>' .
        '<p><a href="index.php?id=' . $id . '">' . $lng['back'] . '</a></p>';
}
