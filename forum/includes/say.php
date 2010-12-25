<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');
if (!$id || !$user_id || $ban['1'] || $ban['11']) {
    header("Location: index.php");
    exit;
}

// Проверка на флуд
$flood = functions::antiflood();
if ($flood) {
    require('../incfiles/head.php');
    echo functions::display_error($lng['error_flood'] . ' ' . $flood . $lng['sec'], '<a href="?id=' . $id . '&amp;start=' . $start . '">' . $lng['back'] . '</a>');
    require('../incfiles/end.php');
    exit;
}

$headmod = 'forum,' . $id . ',1';
$agn1 = strtok($agn, ' ');
$type = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id'");
$type1 = mysql_fetch_assoc($type);
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
        if (isset($_POST['submit']) && !empty($_POST['msg'])) {
            $msg = trim($_POST['msg']);
            if ($_POST['msgtrans'] == 1) {
                $msg = functions::trans($msg);
            }
            // Проверяем, не повторяется ли сообщение?
            $req = mysql_query("SELECT * FROM `forum` WHERE `user_id` = '$user_id' AND `type` = 'm' ORDER BY `time` DESC");
            if (mysql_num_rows($req) > 0) {
                $res = mysql_fetch_array($req);
                if ($msg == $res['text']) {
                    require('../incfiles/head.php');
                    echo functions::display_error($lng['error_message_exists'], '<a href="?id=' . $id . '&amp;start=' . $start . '">' . $lng['back'] . '</a>');
                    require('../incfiles/end.php');
                    exit;
                }
            }
            // Удаляем фильтр, если он был
            if (isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $id) {
                unset($_SESSION['fsort_id']);
                unset($_SESSION['fsort_users']);
            }
            //Обрабатываем ссылки
            $msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', 'functions::forum_link', $msg);
            // Добавляем сообщение в базу
            mysql_query("INSERT INTO `forum` SET
                `refid` = '$id',
                `type` = 'm' ,
                `time` = '$realtime',
                `user_id` = '$user_id',
                `from` = '$login',
                `ip` = '" . long2ip($ip) . "',
                `soft` = '" . mysql_real_escape_string($agn1) . "',
                `text` = '" . mysql_real_escape_string($msg) . "'
            ");
            $fadd = mysql_insert_id();
            // Обновляем время топика
            mysql_query("UPDATE `forum` SET
                `time` = '$realtime'
                WHERE `id` = '$id'
            ");
            // Обновляем статистику юзера
            mysql_query("UPDATE `users` SET
                `postforum`='" . ($datauser['postforum'] + 1) . "',
                `lastpost` = '$realtime'
                WHERE `id` = '$user_id'
            ");
            // Вычисляем, на какую страницу попадает добавляемый пост
            $page = $set_forum['upfp'] ? 1 : ceil(mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `refid` = '$id'" . ($rights >= 7 ? '' : " AND `close` != '1'")), 0) / $kmess);
            if ($_POST['addfiles'] == 1)
                header("Location: index.php?id=$fadd&act=addfile");
            else
                header("Location: index.php?id=$id&page=$page");
        } else {
            require('../incfiles/head.php');
            if ($datauser['postforum'] == 0) {
                if (!isset($_GET['yes'])) {
                    $lng_faq = $core->load_lng('faq');
                    echo '<p>' . $lng_faq['forum_rules_text'] . '</p>' .
                        '<p><a href="index.php?act=say&amp;id=' . $id . '&amp;yes">' . $lng_forum['agree'] . '</a> | ' .
                        '<a href="index.php?id=' . $id . '">' . $lng_forum['not_agree'] . '</a></p>';
                    require('../incfiles/end.php');
                    exit;
                }
            }
            echo '<div class="phdr"><b>' . $lng_forum['topic'] . ':</b> ' . $type1['text'] . '</div>' .
                '<form name="form" action="index.php?act=say&amp;id=' . $id . '&amp;start=' . $start . '" method="post"><div class="gmenu">' .
                '<p><h3>' . $lng_forum['post'] . '</h3>';
            if(!$is_mobile)
                echo '</p><p>' . functions::auto_bb('form', 'msg');
            echo '<textarea cols="' . $set_user['field_w'] . '" rows="' . $set_user['field_h'] . '" name="msg"></textarea></p>' .
                '<p><input type="checkbox" name="addfiles" value="1" /> ' . $lng_forum['add_file'];
            if ($set_user['translit'])
                echo '<br /><input type="checkbox" name="msgtrans" value="1" /> ' . $lng['translit'];
            echo '</p><p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p>' .
                '</div></form>';
        }
        echo '<div class="phdr"><a href="../pages/faq.php?act=trans">' . $lng['translit'] . '</a> | ' .
            '<a href="../pages/faq.php?act=smileys">' . $lng['smileys'] . '</a></div>' .
            '<p><a href="?id=' . $id . '&amp;start=' . $start . '">' . $lng['back'] . '</a></p>';
        break;

    case 'm':
        /*
        -----------------------------------------------------------------
        Добавление сообщения с цитированием поста
        -----------------------------------------------------------------
        */
        $th = $type1['refid'];
        $th2 = mysql_query("SELECT * FROM `forum` WHERE `id` = '$th'");
        $th1 = mysql_fetch_array($th2);
        if (($th1['edit'] == 1 || $th1['close'] == 1) && $rights < 7) {
            require('../incfiles/head.php');
            echo functions::display_error($lng_forum['error_topic_closed'], '<a href="index.php?id=' . $id . '">' . $lng['back'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        $vrp = $type1['time'] + $set_user['sdvig'] * 3600;
        $vr = date("d.m.Y / H:i", $vrp);
        if (isset($_POST['submit'])) {
            if (empty($_POST['msg'])) {
                require('../incfiles/head.php');
                echo functions::display_error($lng['error_empty_message'], '<a href="index.php?act=say&amp;id=' . $th . (isset($_GET['cyt']) ? '&amp;cyt' : '') . '">' . $lng['repeat'] . '</a>');
                require('../incfiles/end.php');
                exit;
            }
            $msg = trim($_POST['msg']);
            if ($_POST['msgtrans'] == 1) {
                $msg = functions::trans($msg);
            }
            $to = $type1['from'];
            if (!empty($_POST['citata'])) {
                // Если была цитата, форматируем ее и обрабатываем
                $citata = trim($_POST['citata']);
                $citata = preg_replace('#\[c\](.*?)\[/c\]#si', '', $citata);
                $citata = mb_substr($citata, 0, 200);
                $tp = date("d.m.Y/H:i", $type1['time']);
                $msg = '[c]' . $to . ' (' . $tp . ")\r\n" . $citata . '[/c]' . $msg;
            } elseif (isset($_POST['txt'])) {
                // Если был ответ, обрабатываем реплику
                $txt = intval($_POST['txt']);
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
            $msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', 'functions::forum_link', $msg);
            // Проверяем, не повторяется ли сообщение?
            $req = mysql_query("SELECT * FROM `forum` WHERE `user_id` = '$user_id' AND `type` = 'm' ORDER BY `time` DESC LIMIT 1");
            if (mysql_num_rows($req) > 0) {
                $res = mysql_fetch_array($req);
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
            // Добавляем сообщение в базу
            mysql_query("INSERT INTO `forum` SET
                `refid` = '$th',
                `type` = 'm',
                `time` = '$realtime',
                `user_id` = '$user_id',
                `from` = '$login',
                `ip` = '" . long2ip($ip) . "',
                `soft` = '" . mysql_real_escape_string($agn1) . "',
                `text` = '" . mysql_real_escape_string($msg) . "'
            ");
            $fadd = mysql_insert_id();
            // Обновляем время топика
            mysql_query("UPDATE `forum`
                SET `time` = '$realtime'
                WHERE `id` = '$th'
            ");
            // Обновляем статистику юзера
            mysql_query("UPDATE `users` SET
                `postforum`='" . ($datauser['postforum'] + 1) . "',
                `lastpost` = '$realtime'
                WHERE `id` = '$user_id'
            ");
            // Вычисляем, на какую страницу попадает добавляемый пост
            $page = $set_forum['upfp'] ? 1 : ceil(mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `refid` = '$th'" . ($rights >= 7 ? '' : " AND `close` != '1'")), 0) / $kmess);
            $addfiles = intval($_POST['addfiles']);
            if ($addfiles == 1) {
                header("Location: index.php?id=$fadd&act=addfile");
            } else {
                header("Location: index.php?id=$th&page=$page");
            }
        } else {
            require('../incfiles/head.php');
            $qt = " $type1[text]";
            if (($datauser['postforum'] == "" || $datauser['postforum'] == 0)) {
                if (!isset($_GET['yes'])) {
                    $lng_faq = $core->load_lng('faq');
                    echo '<p>' . $lng_faq['forum_rules_text'] . '</p>';
                    echo '<p><a href="index.php?act=say&amp;id=' . $id . '&amp;yes&amp;cyt">' . $lng_forum['agree'] . '</a> | <a href="index.php?id=' . $type1['refid'] . '">' . $lng_forum['not_agree'] . '</a></p>';
                    require('../incfiles/end.php');
                    exit;
                }
            }
            echo '<div class="phdr"><b>' . $lng_forum['topic'] . ':</b> ' . $th1['text'] . '</div>';
            $qt = str_replace("<br/>", "\r\n", $qt);
            $qt = trim(preg_replace('#\[c\](.*?)\[/c\]#si', '', $qt));
            $qt = functions::checkout($qt, 0, 2);
            echo '<form name="form" action="?act=say&amp;id=' . $id . '&amp;start=' . $start . '&amp;cyt" method="post">';
            if (isset($_GET['cyt'])) {
                // Форма с цитатой
                echo '<div class="gmenu">' .
                    '<p><b>' . $type1['from'] . '</b> <span class="gray">(' . date("d.m.Y/H:i", $type1['time']) . ')</span></p>' .
                    '<p><h3>' . $lng_forum['cytate'] . '</h3>' .
                    '<textarea cols="' . $set_user['field_w'] . '" rows="' . $set_user['field_h'] . '" name="citata">' . $qt . '</textarea>' .
                    '<br /><small>' . $lng_forum['cytate_help'] . '</small></p>';
            } else {
                // Форма с репликой
                echo '<div class="gmenu"><p><h3>' . $lng_forum['reference'] . '</h3>' .
                    '<input type="radio" value="1" checked="checked" name="txt" />&#160;<b>' . $type1['from'] . '</b>,<br />' .
                    '<input type="radio" value="2" name="txt" />&#160;<b>' . $type1['from'] . '</b>, ' . $lng_forum['reply_1'] . ',<br />' .
                    '<input type="radio" value="3" name="txt" />&#160;<b>' . $type1['from'] . '</b>, ' . $lng_forum['reply_2'] . ' (<a href="index.php?act=post&amp;id=' . $type1['id'] . '">' . $vr . '</a>) ' . $lng_forum['reply_3']
                    . ',<br />' .
                    '<input type="radio" value="4" name="txt" />&#160;<b>' . $type1['from'] . '</b>, ' . $lng_forum['reply_4'] . '</p>';
            }
            echo '<p><h3>' . $lng_forum['post'] . '</h3>';
            if(!$is_mobile)
                echo '</p><p>' . functions::auto_bb('form', 'msg');
            echo '<textarea cols="' . $set_user['field_w'] . '" rows="' . $set_user['field_h'] . '" name="msg"></textarea></p>' .
                '<p><input type="checkbox" name="addfiles" value="1" /> ' . $lng_forum['add_file'];
            if ($set_user['translit'])
                echo '<br /><input type="checkbox" name="msgtrans" value="1" /> ' . $lng['translit'];
            echo '</p><p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p></div></form>';
        }
        echo '<div class="phdr"><a href="../pages/faq.php?act=trans">' . $lng['translit'] . '</a> | ' .
            '<a href="../pages/faq.php?act=smileys">' . $lng['smileys'] . '</a></div>' .
            '<p><a href="?id=' . $type1['refid'] . '&amp;start=' . $start . '">' . $lng['back'] . '</a></p>';
        break;

    default:
        require('../incfiles/head.php');
        echo functions::display_error($lng_forum['error_topic_deleted'], '<a href="index.php">' . $lng['to_forum'] . '</a>');
        require('../incfiles/end.php');
        break;
}
?>