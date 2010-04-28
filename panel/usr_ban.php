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

require_once ('../incfiles/ban.php');
//TODO: Написать Амнистию
switch ($mod) {
    case 'amnesty' :
        if ($rights < 9) {
            echo display_error('Амнистия доступна только для Супервизоров');
        }
        else {
            echo '<div class="phdr"><b>Амнистия</b></div>';
            if (isset ($_POST['submit'])) {
                $term = isset ($_POST['term']) && $_POST['term'] == 1 ? 1 : 0;
                if ($term) {
                    // Очищаем таблицу Банов
                    mysql_query("TRUNCATE TABLE `cms_ban_users`");
                    echo '<div class="gmenu"><p>Таблица Банов очищена.<br />Удалена вся история нарушений</p></div>';
                }
                else {
                    // Разбаниваем активные Баны
                    $req = mysql_query("SELECT * FROM `cms_ban_users` WHERE `ban_time` > '" . $realtime . "'");
                    while ($res = mysql_fetch_array($req)) {
                        $ban_left = $res['ban_time'] - $realtime;
                        if ($ban_left < 2592000) {
                            mysql_query("UPDATE `cms_ban_users` SET `ban_time`='$realtime', `ban_raz`='--Амнистия--' WHERE `id` = '" . $res['id'] . "'");
                        }
                    }
                    echo '<div class="gmenu"><p>Разбанены все пользователи, которые имели активные Баны (кроме банов &quot;До отмены&quot;)</p></div>';
                }
            }
            else {
                echo '<form action="index.php?act=usr_ban&amp;mod=amnesty" method="post"><div class="menu"><p>';
                echo '<input type="radio" name="term" value="0" checked="checked" />&nbsp;Разбанить всех<br />';
                echo '<input type="radio" name="term" value="1" />&nbsp;Очистить базу';
                echo '</p><p><input type="submit" name="submit" value="Амнистия" />';
                echo '</p></div></form>';
                echo '<div class="phdr"><small>&quot;Разбанить всех&quot; - прекращает действие всех активных Банов<br />';
                echo '&quot;Очистить базу&quot; - прекращает действие всех банов и очищает всю историю нарушений</small></div>';
            }
            echo '<p><a href="index.php?act=usr_ban">Бан панель</a><br /><a href="index.php">Админ панель</a></p>';
        }
        break;

    default :
        ////////////////////////////////////////////////////////////
        // Список нарушителей                                     //
        ////////////////////////////////////////////////////////////
        echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | список нарушителей</div>';
        echo '<div class="gmenu"><p><span class="gray">Сортировка:</span> ';
        if (isset ($_GET['count']))
            echo '<a href="index.php?act=usr_ban">Срок</a> | Нарушения</p></div>';
        else
            echo 'Срок | <a href="index.php?act=usr_ban&amp;count">Нарушения</a></p></div>';
        $sort = isset ($_GET['count']) ? 'bancount' : 'bantime';
        $req = mysql_query("SELECT `user_id` FROM `cms_ban_users` GROUP BY `user_id`");
        $total = mysql_num_rows($req);
        $req = mysql_query("SELECT COUNT(`cms_ban_users`.`user_id`) AS `bancount`, MAX(`cms_ban_users`.`ban_time`) AS `bantime`, `users`.*
        FROM `cms_ban_users` LEFT JOIN `users` ON `cms_ban_users`.`user_id` = `users`.`id`
        GROUP BY `user_id`
        ORDER BY `$sort` DESC
        LIMIT $start, $kmess");
        if (mysql_num_rows($req)) {
            while ($res = mysql_fetch_array($req)) {
                echo '<div class="' . ($res['bantime'] > $realtime ? 'r' : '') . 'menu">';
                echo show_user($res, 0, 2, ' [' . $res['bancount'] . ']&nbsp;<a href="../str/users_ban.php?id='.$res['id'].'">&gt;&gt;</a>');
                echo '</div>';
            }
        }
        else {
            echo '<div class="menu"><p>Список пуст</p></div>';
        }
        echo '<div class="phdr">Всего: ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<p>' . pagenav('index.php?act=usr_ban&amp;', $start, $total, $kmess) . '</p>';
            echo '<p><form action="index.php?act=usr_ban" method="post"><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
        }
        echo '<p>' . ($rights == 9 && $total ? '<a href="index.php?act=usr_ban&amp;mod=amnesty">Амнистия</a><br />' : '') . '<a href="index.php">Админ панель</a></p>';
}

require_once ("../incfiles/end.php");

?>