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
    echo functions::display_error($lng['error_flood'] . ' ' . $flood . $lng['sec'] . ', <a href="index.php?id=' . $id . '&amp;start=' . $start . '">' . $lng['back'] . '</a>');
    require('../incfiles/end.php');
    exit;
}
$req_r = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 'r' LIMIT 1");
if (!mysql_num_rows($req_r)) {
    require('../incfiles/head.php');
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}
if (isset($_POST['submit'])) {
    $error = array();
    $th = isset($_POST['th']) ? trim($_POST['th']) : '';
    $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';
    if (empty($th))
        $error[] = $lng_forum['error_topic_name'];
    if (mb_strlen($th) < 2)
        $error[] = $lng_forum['error_topic_name_lenght'];
    if (empty($msg))
        $error[] = $lng['error_empty_message'];
    if (!$error) {
        $th = functions::check(mb_substr($th, 0, 100));
        if ($_POST['msgtrans'] == 1) {
            $th = functions::trans($th);
            $msg = functions::trans($msg);
        }
        $msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', 'functions::forum_link', $msg);
        // Прверяем, есть ли уже такая тема в текущем разделе?
        if (mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `refid` = '$id' AND `text` = '$th'"), 0) > 0)
            $error[] = $lng_forum['error_topic_exists'];
        // Проверяем, не повторяется ли сообщение?
        $req = mysql_query("SELECT * FROM `forum` WHERE `user_id` = '$user_id' AND `type` = 'm' ORDER BY `time` DESC");
        if (mysql_num_rows($req) > 0) {
            $res = mysql_fetch_array($req);
            if ($msg == $res['text'])
                $error[] = $lng['error_message_exists'];
        }
    }
    if (!$error) {
        // Добавляем тему
        mysql_query("INSERT INTO `forum` SET
            `refid` = '$id',
            `type` = 't',
            `time` = '$realtime',
            `user_id` = '$user_id',
            `from` = '$login',
            `text` = '$th'
        ");
        $rid = mysql_insert_id();
        // Добавляем текст поста
        mysql_query("INSERT INTO `forum` SET
            `refid` = '$rid',
            `type` = 'm',
            `time` = '$realtime',
            `user_id` = '$user_id',
            `from` = '$login',
            `ip` = '" . long2ip($ip) . "',
            `soft` = '" . mysql_real_escape_string($agn) . "',
            `text` = '" . mysql_real_escape_string($msg) . "'
        ");
        $postid = mysql_insert_id();
        // Записываем счетчик постов юзера
        $fpst = $datauser['postforum'] + 1;
        mysql_query("UPDATE `users` SET
            `postforum` = '$fpst',
            `lastpost` = '$realtime'
            WHERE `id` = '$user_id'
        ");
        // Ставим метку о прочтении
        mysql_query("INSERT INTO `cms_forum_rdm` SET
            `topic_id`='$rid',
            `user_id`='$user_id',
            `time`='$realtime'
        ");
        if ($_POST['addfiles'] == 1)
            header("Location: index.php?id=$postid&act=addfile");
        else
            header("Location: index.php?id=$rid");
    } else {
        // Выводим сообщение об ошибке
        require('../incfiles/head.php');
        echo functions::display_error($error, '<a href="index.php?act=nt&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
        require('../incfiles/end.php');
        exit;
    }
} else {
    $res_r = mysql_fetch_assoc($req_r);
    $req_c = mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $res_r['refid'] . "'");
    $res_c = mysql_fetch_assoc($req_c);
    require('../incfiles/head.php');
    if ($datauser['postforum'] == 0) {
        if (!isset($_GET['yes'])) {
            $lng_faq = $core->load_lng('faq');
            echo '<p>' . $lng_faq['forum_rules_text'] . '</p>';
            echo '<p><a href="index.php?act=nt&amp;id=' . $id . '&amp;yes">' . $lng_forum['agree'] . '</a> | <a href="index.php?id=' . $id . '">' . $lng_forum['not_agree'] . '</a></p>';
            require('../incfiles/end.php');
            exit;
        }
    }
    echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['new_topic'] . '</div>' .
        '<form name="form" action="index.php?act=nt&amp;id=' . $id . '" method="post">' .
        '<div class="gmenu">' .
        '<p><h3>' . $lng['section'] . '</h3>' .
        '<a href="index.php?id=' . $res_c['id'] . '">' . $res_c['text'] . '</a> | <a href="index.php?id=' . $res_r['id'] . '">' . $res_r['text'] . '</a></p>' .
        '<p><h3>' . $lng_forum['new_topic_name'] . '</h3>' .
        '<input type="text" size="20" maxlength="100" name="th"/></p>' .
        '<p><h3>' . $lng_forum['post'] . '</h3>';
    if(!$is_mobile)
        echo '</p><p>' . functions::auto_bb('form', 'msg');
    echo '<textarea cols="' . $set_user['field_w'] . '" rows="' . $set_user['field_h'] . '" name="msg"></textarea></p>' .
        '<p><input type="checkbox" name="addfiles" value="1" /> ' . $lng_forum['add_file'];
    if ($set_user['translit'])
        echo '<br /><input type="checkbox" name="msgtrans" value="1" /> ' . $lng['translit'];
    echo '</p><p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p></div></form>' .
        '<div class="phdr"><a href="../pages/faq.php?act=trans">' . $lng['translit'] . '</a> | ' .
        '<a href="../pages/faq.php?act=smileys">' . $lng['smileys'] . '</a></div>' .
        '<p><a href="index.php?id=' . $id . '">' . $lng['back'] . '</a></p>';
}
?>