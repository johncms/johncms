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
if ($rights == 3 || $rights >= 6) {
    $topic_vote = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote` WHERE `type`='1' AND `topic`='$id'"), 0);
    require_once ("../incfiles/head.php");
    if ($topic_vote == 0) {
        echo 'Ошибка!!!<br /> <a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">назад</a>';
        require_once ("../incfiles/end.php");
        exit;
    }
    if (isset ($_GET['delvote']) && !empty ($_GET['vote'])) {
        $vote = abs(intval($_GET['vote']));
        $totalvote = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote` WHERE `type`='2' AND `id`='$vote' AND `topic` = '$id'"), 0);
        $countvote = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote` WHERE `type`='2' AND `topic`='" . $id . "'"), 0);
        if ($countvote <= 2)
            header('location: ?act=editvote&id=' . $id . '');
        if ($totalvote != 0) {
            if (isset ($_GET['yes'])) {
                mysql_query("DELETE FROM `forum_vote` WHERE `id` = '$vote'");
                $countus = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote_us` WHERE `vote` = '" . $vote . "' AND `topic`='" . $id . "'"), 0);
                $topic_vote = mysql_fetch_array(mysql_query("SELECT `count` FROM `forum_vote` WHERE `type`='1' AND `topic`='$id' LIMIT 1"));
                $totalcount = $topic_vote['count'] - $countus;
                mysql_query("UPDATE `forum_vote` SET  `count`='" . $totalcount . "'   WHERE `type` = '1' AND `topic` = '" . $id . "'");
                mysql_query("DELETE FROM `forum_vote_us` WHERE `vote` = '$vote'");
                header('location: ?act=editvote&id=' . $id . '');
            }
            else {
                echo '<p>Вы действительно хотите удалить вариант ответа?</p>';
                echo '<p><a href="index.php?act=editvote&amp;id=' . $id . '&amp;vote=' . $vote . '&amp;delvote&amp;yes">Удалить</a><br />';
                echo '<a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Отмена</a></p>';
            }
        }
        else {
            header('location: ?act=editvote&id=' . $id . '');
        }
    }
    else
        if (isset ($_POST['submit'])) {
            $vote_name = mb_substr(trim($_POST['name_vote']), 0, 50);
            if (!empty ($vote_name))
                mysql_query("UPDATE `forum_vote` SET  `name`='" . mysql_real_escape_string($vote_name) . "'  WHERE `topic` = '$id' AND `type` = '1'");
            $vote_result = mysql_query("SELECT `id` FROM `forum_vote` WHERE `type`='2' AND `topic`='" . $id . "'");
            while ($vote = mysql_fetch_array($vote_result)) {
                if (!empty ($_POST[$vote['id'] . 'vote'])) {
                    $text = mb_substr(trim($_POST[$vote['id'] . 'vote']), 0, 30);
                    mysql_query("UPDATE `forum_vote` SET  `name`='" . mysql_real_escape_string($text) . "'  WHERE `id` = '" . $vote['id'] . "'");
                }
            }
            $countvote = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote` WHERE `type`='2' AND `topic`='" . $id . "'"), 0);
            for ($vote = $countvote; $vote < 8; $vote++) {
                if (!empty ($_POST[$vote])) {
                    $text = mb_substr(trim($_POST[$vote]), 0, 30);
                    mysql_query("INSERT INTO `forum_vote` SET `name`='" . mysql_real_escape_string($text) . "',  `type` = '2', `topic`='" . $id . "';");
                }
            }
            echo 'Опрос изменен<br /><a href="?id=' . $id . '">Продолжить</a>';
        }
        else {
            $countvote = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote` WHERE `type`='2' AND `topic`='" . $id . "'"), 0);
            $topic_vote = mysql_fetch_array(mysql_query("SELECT `name` FROM `forum_vote` WHERE `type`='1' AND `topic`='$id' LIMIT 1"));
            echo '<form action="index.php?act=editvote&amp;id=' . $id . '" method="post">';
            echo '<br />Опрос(max. 150):<br/><input type="text" size="20" maxlength="150" name="name_vote" value="' . htmlentities($topic_vote['name'], ENT_QUOTES, 'UTF-8') . '"/><br/>';
            $vote_result = mysql_query("SELECT `id`, `name` FROM `forum_vote` WHERE `type`='2' AND `topic`='" . $id . "'");
            while ($vote = mysql_fetch_array($vote_result)) {
                echo 'Ответ ' . ($i + 1) . '(max. 50): <br/><input type="text" name="' . $vote['id'] . 'vote" value="' . htmlentities($vote['name'], ENT_QUOTES, 'UTF-8') . '"/>';
                if ($countvote > 2)
                    echo '[<a href="index.php?act=editvote&amp;id=' . $id . '&amp;vote=' . $vote['id'] . '&amp;delvote">del</a>]';
                echo '<br/>';
                ++$i;
            }
            for ($vote = $i; $vote < 8; $vote++) {
                echo 'Ответ ' . ($vote + 1) . '(max. 50): <br/><input type="text" name="' . $vote . '"/><br/>';
            }
            echo '<p><input type="submit" name="submit" value="Отправить"/></p></form>';
            echo '<a href="?id=' . $id . '">в тему</a>';
    }
}
else {
    header('location: ../index.php?err');
}

?>