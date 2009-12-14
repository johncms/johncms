<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights == 3 || $rights >= 6) {
    if (empty ($_GET['id'])) {
        require_once ("../incfiles/head.php");
        echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    // Проверяем, существует ли тема
    $req = mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $id . "'");
    $res = mysql_fetch_array($req);
    if ($res['type'] != 't') {
        require_once ("../incfiles/head.php");
        echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    if (isset ($_GET['yes']) && $rights == 9) {
        // Удаляем прикрепленные файлы
        $req1 = mysql_query("SELECT * FROM `cms_forum_files` WHERE `topic` = '$id'");
        if (mysql_num_rows($req1)) {
            while ($res1 = mysql_fetch_array($req1)) {
                unlink('files/' . $res1['filename']);
            }
            mysql_query("DELETE FROM `cms_forum_files` WHERE `topic` = '$id'");
            mysql_query("OPTIMIZE TABLE `cms_forum_files`");
        }
        // Удаляем посты топика
        mysql_query("DELETE FROM `forum` WHERE `refid` = '$id'");
        // Удаляем топик
        mysql_query("DELETE FROM `forum` WHERE `id`='$id'");
        header('Location: ?id=' . $res['refid']);
    }
    elseif (isset ($_GET['hid']) || isset ($_GET['yes']) && $rights < 9) {
        // Скрываем топик
        mysql_query("UPDATE `forum` SET `close` = '1' WHERE `id` = '" . $id . "' LIMIT 1");
        // Скрываем прикрепленные файлы
        $req1 = mysql_query("SELECT * FROM `cms_forum_files` WHERE `topic` = '$id'");
        if (mysql_num_rows($req1) > 0) {
            while ($res1 = mysql_fetch_array($req1)) {
                mysql_query("UPDATE `cms_forum_files` SET `del` = '1' WHERE `id` = '" . $res1['id'] . "'");
            }
        }
        header('Location: ?id=' . $res['refid']);
    }

    require_once ("../incfiles/head.php");
    echo '<p>Вы действительно хотите удалить тему?</p>';
    echo '<p><a href="?act=deltema&amp;id=' . $id . '&amp;yes">Удалить</a><br />';
    if ($rights == 9 && $res['close'] != 1) {
        echo '<a href="?act=deltema&amp;id=' . $id . '&amp;hid">Скрыть</a><br />';
    }
    echo '<a href="?id=' . $id . '">Отмена</a></p>';
}
else {
    echo '<p>Доступ закрыт!!!</p>';
}

?>