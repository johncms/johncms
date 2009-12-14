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

define('_IN_JOHNCMS', 1);

require_once ("../incfiles/head.php");
$topic_vote = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote` WHERE `type`='1' AND `topic`='$id'"), 0);
if ($topic_vote == 0) {
    echo 'Ошибка!!!<br /> <a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">назад</a>';
    require_once ("../incfiles/end.php");
}
else {
    $topic_vote = mysql_fetch_array(mysql_query("SELECT `name`, `time`, `count` FROM `forum_vote` WHERE `type`='1' AND `topic`='$id' LIMIT 1"));
    echo '<div  class="phdr">Пользователи, принявшие участие в опросе &laquo;<b>' . htmlentities($topic_vote['name'], ENT_QUOTES, 'UTF-8') . '</b>&raquo;</div>';
    $colmes = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote_us` WHERE `topic`='$id'"), 0);
    $req = mysql_query(
    "SELECT `forum_vote_us`.*, `users`.`rights`, `users`.`lastdate`, `users`.`name`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
    FROM `forum_vote_us` LEFT JOIN `users` ON `forum_vote_us`.`user` = `users`.`id` WHERE `forum_vote_us`.`topic`='$id' ORDER BY `time` DESC LIMIT "
    . $start . "," . $kmess);
    while ($res = mysql_fetch_array($req)) {
        echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
        if ($res['sex'])
            echo '<img src="../theme/' . $set_user['skin'] . '/images/' . ($res['sex'] == 'm' ? 'm' : 'f') . ($res['datereg'] > $realtime - 86400 ? '_new.gif" width="20"' : '.gif" width="16"') . ' height="16"/>&nbsp;';
        else
            echo '<img src="../images/del.png" width="12" height="12" />&nbsp;';
        if (!$user_id || $user_id == $res['id']) {
            print '<b>' . $res['name'] . '</b> ';
        }
        else {
            print "<a href='../str/anketa.php?id=" . $res['id'] . "'>$res[name]</a> ";
        }
        $user_rights = array(1 => 'Kil', 3 => 'Mod', 6 => 'Smd', 7 => 'Adm', 8 => 'SV');
        echo $user_rights[$res['rights']];
        $ontime = $res['lastdate'];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2) {
            echo '<span class="red"> [Off]</span><br/>';
        }
        else {
            echo '<span class="green"> [ON]</span><br/>';
        }
        echo '</div>';
        ++$i;
    }
    if ($colmes == 0)
        echo '<div class="menu">В этом опросе пока никто не участвовал!</div>';
    echo '<div class="bmenu">Всего: ' . $colmes . '</div>';
    if ($colmes > $kmess) {
        echo '<p>' . pagenav('index.php?act=users&amp;id=' . $id . '&amp;', $start, $colmes, $kmess) . '</p>';
        echo '<p><form action="index.php" method="get"><input type="hidden" name="act" value="users"/><input type="hidden" name="id" value="' . $id .
        '"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
    }
    echo '<a href="index.php?id=' . $id . '">в тему</a>';
}
require_once ("../incfiles/end.php");

?>