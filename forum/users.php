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

require_once ('../incfiles/head.php');
$topic_vote = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote` WHERE `type` = '1' AND `topic` = '$id'"), 0);
if ($topic_vote == 0) {
    echo 'Ошибка!!!<br /> <a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">назад</a>';
    require_once ('../incfiles/end.php');
}
else {
    $topic_vote = mysql_fetch_array(mysql_query("SELECT `name`, `time`, `count` FROM `forum_vote` WHERE `type` = '1' AND `topic` = '$id' LIMIT 1"));
    echo '<div  class="phdr">Пользователи, принявшие участие в опросе &laquo;<b>' . htmlentities($topic_vote['name'], ENT_QUOTES, 'UTF-8') . '</b>&raquo;</div>';
    $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote_us` WHERE `topic`='$id'"), 0);
    $req = mysql_query("SELECT `forum_vote_us`.*, `users`.`rights`, `users`.`lastdate`, `users`.`name`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
    FROM `forum_vote_us` LEFT JOIN `users` ON `forum_vote_us`.`user` = `users`.`id`
    WHERE `forum_vote_us`.`topic`='$id' ORDER BY `time` DESC LIMIT $start,$kmess");
    $set_user['avatar'] = 0;
    while ($res = mysql_fetch_array($req)) {
        echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
        echo show_user($res, 0, 0, '</div>');
        ++$i;
    }
    if ($total == 0)
        echo '<div class="menu">В этом опросе пока никто не участвовал!</div>';
    echo '<div class="phdr">Всего: ' . $total . '</div>';
    if ($total > $kmess) {
        echo '<p>' . pagenav('index.php?act=users&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</p>';
        echo '<p><form action="index.php" method="get"><input type="hidden" name="act" value="users"/><input type="hidden" name="id" value="' . $id .
        '"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
    }
    echo '<p><a href="index.php?id=' . $id . '">В тему</a></p>';
}
require_once ("../incfiles/end.php");

?>