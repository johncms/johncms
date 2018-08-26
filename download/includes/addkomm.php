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

if ($id && $user_id && !isset($ban['1']) && !isset($ban['10']) && ($set['mod_down_comm'] || $rights >= 7)) {
    $file = $db->query("SELECT * FROM `download` WHERE `type` = 'file' AND `id` = '" . $id . "' LIMIT 1");
    if (!$file->rowCount()) {
        require_once("../incfiles/head.php");
        echo functions::display_error($lng['error_wrong_data'], '<a href="?">' . $lng['back'] . '</a>');
        require_once('../incfiles/end.php');
        exit;
    }
    if (isset ($_POST['submit'])) {
        $msg = isset($_POST['msg']) ? trim(mb_substr(functions::checkin($_POST['msg']), 0, 500)) : '';
        // Проверка на флуд
        $flood = functions::antiflood();
        if ($flood) {
            require_once('../incfiles/head.php');
            echo functions::display_error($lng['error_flood'] . ' ' . $flood . $lng['sec'] . '.', '<a href="index.php?act=komm&amp;id=' . $id . '">' . $lng['back'] . '</a>');
            require_once('../incfiles/end.php');
            exit;
        }
        if (empty($msg)) {
            require_once("../incfiles/head.php");
            echo functions::display_error($lng['error_empty_fields'], '<a href="?act=komm&amp;id="' . $id . '">' . $lng['back'] . '</a>');
            require_once('../incfiles/end.php');
            exit;
        }
        if (isset($_POST['msgtrans'])) {
            $msg = functions::trans($msg);
        }
        $agn = strtok($agn, ' ');
        $stmt = $db->prepare("INSERT INTO `download` SET
            `refid` = '$id',
            `adres` = '',
            `time` = '" . time() . "',
            `name` = '',
            `type` = 'komm',
            `avtor` = ?,
            `ip` = '" . long2ip($ip) . "',
            `soft` = ?,
            `text` = ?,
            `screen` = ''
        ");
        $stmt->execute([
            $login,
            $agn,
            $msg
        ]);
        $fpst = $datauser['komm'] + 1;
        $db->exec("UPDATE `users` SET `komm`='" . $fpst . "', `lastpost` = '" . time() . "' WHERE `id`='" . $user_id . "'");
        header("Location: index.php?act=komm&id=$id"); exit;
    } else {
        require_once("../incfiles/head.php");
        echo "<form action='?act=addkomm&amp;id=" . $id . "' method='post'>" .
            $lng['message'] . " (max. 500)<br/>" .
            "<textarea rows='3' name='msg' ></textarea><br/><br/>" .
            "<input type='submit' name='submit' value='" . $lng['add'] . "' />" .
            "</form>";
    }
} else {
    require_once("../incfiles/head.php");
    echo "Вы не авторизованы!<br/>";
}
echo '<br/><a href="?act=komm&amp;id=' . $id . '">' . $lng['comments'] . '</a><br/><a href="?act=view&amp;file=' . $id . '">' . $lng_dl['file'] . '</a><br/>';