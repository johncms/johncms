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

defined('_IN_JOHNADM') or die('Error: restricted access');

if ($rights != 9)
    die('Error: restricted access');

$error = false;
if ($id && $id != $user_id) {
    // Получаем данные юзера
    $req = mysql_query("SELECT * FROM `users` WHERE `id` = '$id' LIMIT 1");
    if (mysql_num_rows($req)) {
        $user = mysql_fetch_assoc($req);
        if ($user['rights'] > $datauser['rights'])
            $error = 'Вы не можете удалять старшего Вас по должности';
        if ($user['immunity'])
            $error = 'Данный пользователь имеет иммунитет.';
    }
    else {
        $error = 'Такого пользователя не существует';
    }
}
else {
    $error = 'Не указан пользователь';
}

if (!$error) {
    //TODO: После доработки модулей, переделать запросы на User ID и чистку Чата
    $req_a = mysql_query("SELECT * FROM `gallery` WHERE `type` = 'al' AND `user` = '1' AND `avtor` = '" . $user['name'] . "' LIMIT 1");
    if (mysql_num_rows($req_a)) {
        $res_a = mysql_fetch_assoc($req_a);
        $album = 1;
        $images_count = mysql_result(mysql_query("SELECT COUNT(*) FROM `gallery` WHERE `refid` = '" . $res_a['id'] . "' AND `type` = 'ft'"), 0);
    }
    else {
        $album = 0;
        $images_count = 0;
    }
    $comm_gal = mysql_result(mysql_query("SELECT COUNT(*) FROM `gallery` WHERE `avtor` = '" . $user['name'] . "' AND `type` = 'km'"), 0);
    $comm_lib = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `avtor` = '" . $user['name'] . "' AND `type` = 'komm'"), 0);
    $comm_dl = mysql_result(mysql_query("SELECT COUNT(*) FROM `download` WHERE `avtor` = '" . $user['name'] . "' AND `type` = 'komm'"), 0);
    $comm_count = $comm_gal + $comm_lib + $comm_dl;
    $guest_count = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `user_id` = '" . $user['id'] . "'"), 0);
    $forumt_count = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 't' AND `close` != '1'"), 0);
    $forump_count = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 'm'  AND `close` != '1'"), 0);
    echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Удаление пользователя</div>';
    // Выводим краткие данные
    echo '<div class="rmenu"><p>' . show_user($user, 0, 1) . '</p></div>';
    switch ($mod) {
        case 'del' :
            // Удаляем личный альбом
            if ($album && isset ($_POST['gallery'])) {
                if ($images_count) {
                    $req = mysql_query("SELECT COUNT(*) FROM `gallery` WHERE `refid` = '" . $res_a['id'] . "' AND `type` = 'ft'");
                    while ($res = mysql_fetch_assoc($req)) {
                        if (file_exists('../gallery/foto/' . $res['name']))
                            unlink('../gallery/foto/' . $res['name']);
                    }
                }
                mysql_query("DELETE FROM `gallery` WHERE `refid` = '" . $res_a['id'] . "'");
                mysql_query("DELETE FROM `gallery` WHERE `id` = '" . $res_a['id'] . "'");
                mysql_query("OPTIMIZE TABLE `gallery`");
            }
            // Удаляем почту
            mysql_query("DELETE FROM `privat` WHERE `user` = '" . $user['name'] . "'");
            mysql_query("DELETE FROM `privat` WHERE `author` = '" . $user['name'] . "' AND `type` = 'out' AND `chit` = 'no'");
            mysql_query("OPTIMIZE TABLE `privat`");
            // Удаляем комментарии
            if ($comm_count && isset ($_POST['comments'])) {
                if ($comm_gal) {
                    // Удаляем из Галреи
                    mysql_query("DELETE FROM `gallery` WHERE `avtor` = '" . $user['name'] . "' AND `type` = 'km'");
                    mysql_query("OPTIMIZE TABLE `gallery`");
                }
                if ($comm_lib) {
                    // Удаляем из Библиотеки
                    mysql_query("DELETE FROM `lib` WHERE `avtor` = '" . $user['name'] . "' AND `type` = 'komm'");
                    mysql_query("OPTIMIZE TABLE `lib`");
                }
                if ($comm_dl) {
                    // Удаляем из Загрузок
                    mysql_query("DELETE FROM `download` WHERE `avtor` = '" . $user['name'] . "' AND `type` = 'komm'");
                    mysql_query("OPTIMIZE TABLE `download`");
                }
            }
            // Удаляем посты в Гостевой
            if ($guest_count && isset ($_POST['guest'])) {
                mysql_query("DELETE FROM `guest` WHERE `user_id` = '" . $user['id'] . "'");
                mysql_query("OPTIMIZE TABLE `guest`");
            }
            // Скрываем темы на форуме
            if ($forumt_count && isset ($_POST['forumt'])) {
                mysql_query("UPDATE `forum` SET `close` = '1' WHERE `type` = 't' AND `user_id` = '" . $user['id'] . "'");
            }
            // Скрываем посты на форуме
            if (isset ($_POST['forump'])) {
                mysql_query("UPDATE `forum` SET `close` = '1' WHERE `type` = 'm' AND `user_id` = '" . $user['id'] . "'");
            }
            // Удаляем пользователя
            mysql_query("DELETE FROM `cms_ban_users` WHERE `user_id` = '" . $user['id'] . "'");
            mysql_query("OPTIMIZE TABLE `cms_ban_users`");
            mysql_query("DELETE FROM `users` WHERE `id` = '" . $user['id'] . "' LIMIT 1");
            echo '<div class="rmenu"><p><h3>Пользователь удален!</h3></p></div>';
            break;

        default :
            ////////////////////////////////////////////////////////////
            // Форма параметров удаления                              //
            ////////////////////////////////////////////////////////////
            echo '<form action="index.php?act=usr_del&amp;mod=del&amp;id=' . $user['id'] . '" method="post"><div class="menu"><p><h3>Чистка активности</h3>';
            if ($album)
                echo '<div><input type="checkbox" value="1" name="gallery" checked="checked" />&nbsp;Галерея, удалить альбом <span class="red">(' . $images_count . ')</span></div>';
            if ($comm_count)
                echo '<div><input type="checkbox" value="1" name="comments" checked="checked" />&nbsp;Комментарии <span class="red">(' . $comm_count . ')</span></div>';
            if ($guest_count)
                echo '<div><input type="checkbox" value="1" name="guest" checked="checked" />&nbsp;Гостевая <span class="red">(' . $guest_count . ')</span></div>';
            if ($forumt_count)
                echo '<div><input type="checkbox" value="1" name="forumt" checked="checked" />&nbsp;Форум, темы <span class="red">(' . $forumt_count . ')</span></div>';
            if ($forump_count)
                echo '<div><input type="checkbox" value="1" name="forump" checked="checked" />&nbsp;Форум, посты <span class="red">(' . $forump_count . ')</span></div>';
            if ($forumt_count || $forump_count)
                echo '<small><span class="gray">При чистке форума, темы и посты переходят в режим "скрытые"</span></small>';
            echo '</p></div><div class="rmenu"><p>Вы действительно хотите удалить данного пользователя?';
            echo '</p><p><input type="submit" value="Удалить" name="submit" />';
            echo '</p></div></form>';
            echo '<div class="phdr"><a href="../str/anketa.php?id=' . $user['id'] . '">В анкету</a></div>';
    }
}
else {
    echo display_error($error);
}
echo '<p><a href="index.php?act=usr_list">Список пользователей</a><br /><a href="index.php">Админ панель</a></p>';

?>