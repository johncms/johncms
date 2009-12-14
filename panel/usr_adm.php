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

echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Список должностных лиц</div>';
$req = mysql_query("SELECT * FROM `users` WHERE `rights` = '9' ORDER BY `name` ASC");
if (mysql_num_rows) {
    echo '<div class="bmenu">Супервайзоры</div>';
    while ($res = mysql_fetch_assoc($req)) {
        echo ($sw % 2) ? '<div class="list2">' : '<div class="list1">';
        echo show_user($res, 0, 2, 0, 0);
        echo '</div>';
        ++$sw;
    }
}
$req = mysql_query("SELECT * FROM `users` WHERE `rights` = '7' ORDER BY `name` ASC");
if (mysql_num_rows) {
    echo '<div class="bmenu">Администраторы</div>';
    while ($res = mysql_fetch_assoc($req)) {
        echo ($adm % 2) ? '<div class="list2">' : '<div class="list1">';
        echo show_user($res, 0, 2, 0, 0);
        echo '</div>';
        ++$adm;
    }
}
$req = mysql_query("SELECT * FROM `users` WHERE `rights` = '6' ORDER BY `name` ASC");
if (mysql_num_rows) {
    echo '<div class="bmenu">Старшие модераторы</div>';
    while ($res = mysql_fetch_assoc($req)) {
        echo ($smd % 2) ? '<div class="list2">' : '<div class="list1">';
        echo show_user($res, 0, 2, 0, 0);
        echo '</div>';
        ++$smd;
    }
}
$req = mysql_query("SELECT * FROM `users` WHERE `rights` BETWEEN '1' AND '5' ORDER BY `name` ASC");
if (mysql_num_rows) {
    echo '<div class="bmenu">Модераторы</div>';
    while ($res = mysql_fetch_assoc($req)) {
        echo ($mod % 2) ? '<div class="list2">' : '<div class="list1">';
        echo show_user($res, 0, 2, 0, 0);
        echo '</div>';
        ++$mod;
    }
}
echo '<div class="phdr">Всего: ' . ($sw + $adm + $smd + $mod) . '</div>';

echo '<p><a href="index.php?act=usr_list">Список пользователей</a><br /><a href="index.php">Админ панель</a></p>';

?>