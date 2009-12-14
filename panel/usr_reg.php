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

echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Подтверждение регистраций</div>';
switch ($mod) {
    case 'approve' :
        if (mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `id` = '$id'"), 0)) {
            mysql_query("UPDATE `users` SET `preg` = '1', `regadm` = '$login' WHERE `id` = '$id'");
            echo '<div class="menu"><p>Регистрация подтверждена<br /><a href="index.php?act=usr_reg">Вернуться</a></p></div>';
        }
        else {
            echo display_error('Такого пользователя не существует');
        }
        break;

    default :
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `preg` = '0'"), 0);
        if ($total) {
            $req = mysql_query("SELECT * FROM `users` WHERE `preg` = '0' ORDER BY `id` DESC LIMIT $start,$kmess");
            while ($res = mysql_fetch_assoc($req)) {
                $link = '<a href="index.php?act=usr_reg&amp;mod=approve&amp;id=' . $res['id'] . '">Подтвердить</a> | <span class="red"><a href="index.php?act=usr_del&amp;id=' . $res['id'] . '">Удалить</a></span>';
                echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
                echo show_user($res, 0, 2, ' ID:' . $res['id'], 0, $link);
                echo '</div>';
                ++$i;
            }
        }
        else {
            echo '<div class="menu"><p>На регистрации никого нет</p></div>';
        }
        echo '<div class="phdr">Всего: ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<p>' . pagenav('index.php?act=usr_reg&amp;', $start, $total, $kmess) . '</p>';
            echo '<p><form action="index.php?act=usr_reg" method="post"><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
        }
        echo '<p><a href="index.php">Админ панель</a></p>';
}

?>