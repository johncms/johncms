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
// Модуль голосований от FlySelf
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');
if ($user_id) {
    $topic = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type`='t' AND `id`='$id' AND `edit` != '1'"), 0);
    $vote = abs(intval($_POST['vote']));
    $topic_vote = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote` WHERE `type`='2' AND `id`='$vote' AND `topic`='$id'"), 0);
    $vote_user = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote_us` WHERE `user`='$user_id' AND `topic`='$id'"), 0);
    require_once ("../incfiles/head.php");
    if ($topic_vote == 0 || $vote_user > 0 || $topic == 0) {
        echo 'Ошибка голосования <br /> <a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">назад</a>';
        require_once ("../incfiles/end.php");
        exit;
    }
    mysql_query("INSERT INTO `forum_vote_us` SET `topic`='" . $id . "', `user`='" . $user_id . "', `vote`='" . $vote . "';");
    mysql_query("UPDATE `forum_vote` SET `count` = count + 1 WHERE id = '" . $vote . "';");
    mysql_query("UPDATE `forum_vote` SET `count` = count + 1 WHERE topic = '" . $id . "' AND `type` = '1';");
    echo 'Ваш голос принят <br /> <a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">назад</a>';
}
else {
    echo 'Вы не авторизованны';
}

?>