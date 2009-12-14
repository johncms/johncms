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
    $topic_vote = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote` WHERE `type`='1' AND `topic`='$id'"), 0);
    require_once ("../incfiles/head.php");
    if ($topic_vote == 0) {
        echo 'Ошибка удаления опроса <br /> <a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">назад</a>';
        require_once ("../incfiles/end.php");
        exit;
    }
    if (isset ($_GET['yes'])) {
        mysql_query("DELETE FROM `forum_vote` WHERE `topic` = '$id'");
        mysql_query("DELETE FROM `forum_vote_us` WHERE `topic` = '$id'");
        mysql_query("UPDATE `forum` SET  `realid` = '0'  WHERE `id` = '$id'");
        echo 'Опрос удален<br /><a href="' . $_SESSION['prd'] . '">Продолжить</a>';
    }
    else {
        echo '<p>Вы действительно хотите удалить опрос?</p>';
        echo '<p><a href="?act=delvote&amp;id=' . $id . '&amp;yes">Удалить</a><br />';
        echo '<a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Отмена</a></p>';
        $_SESSION['prd'] = htmlspecialchars(getenv("HTTP_REFERER"));
    }
}
else {
    header('location: ../index.php?err');
}

?>