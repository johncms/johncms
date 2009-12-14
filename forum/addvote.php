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
    $topic = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type`='t' AND `id`='$id' AND `edit` != '1'"), 0);
    $topic_vote = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote` WHERE `type`='1' AND `topic`='$id'"), 0);
    require_once ("../incfiles/head.php");
    if ($topic_vote != 0 || $topic == 0) {
        echo 'Ошибка!!!<br /> <a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">назад</a>';
        require_once ("../incfiles/end.php");
        exit;
    }
    if (isset ($_POST['submit'])) {
        $vote_name = mb_substr(trim($_POST['name_vote']), 0, 50);
        if (!empty ($vote_name) && !empty ($_POST[0]) && !empty ($_POST[1]) && !empty ($_POST['count_vote'])) {
            mysql_query("INSERT INTO `forum_vote` SET `name`='" . mysql_real_escape_string($vote_name) . "', `time`='" . $realtime . "', `type` = '1', `topic`='" . $id . "';");
            mysql_query("UPDATE `forum` SET  `realid` = '1'  WHERE `id` = '$id'");
            $vote_count = abs(intval($_POST['count_vote']));
            if ($vote_count > 8)
                $vote_count = 8;
            else
                if ($vote_count < 2)
                    $vote_count = 2;

                for ($vote = 0; $vote < $vote_count; $vote++) {
                    $text = mb_substr(trim($_POST[$vote]), 0, 30);
                    if (empty ($text)) {
                        continue;
                    }

                    mysql_query("INSERT INTO `forum_vote` SET `name`='" . mysql_real_escape_string($text) . "',  `type` = '2', `topic`='" . $id . "';");
            }
            echo 'Опрос добавлен<br /><a href="?id=' . $id . '">Продолжить</a>';
        }
        else
            echo 'Ошибка добавления опроса<br /><a href="?act=addvote&amp;id=' . $id . '">повторить</a>';
    }
    else {
        echo '<form action="index.php?act=addvote&amp;id=' . $id . '" method="post">';
        echo '<br />Опрос(max. 150):<br/><input type="text" size="20" maxlength="150" name="name_vote" value="' . htmlentities($_POST['name_vote'], ENT_QUOTES, 'UTF-8') . '"/><br/>';
        if (isset ($_POST['plus']))
            ++$_POST['count_vote'];
        elseif (isset ($_POST['minus']))
            --$_POST['count_vote'];
        if ($_POST['count_vote'] < 2 || empty ($_POST['count_vote']))
            $_POST['count_vote'] = 2;
        elseif ($_POST['count_vote'] > 8)
            $_POST['n'] = 8;
        for ($vote = 0; $vote < $_POST['count_vote']; $vote++) {
            echo 'Ответ ' . ($vote + 1) . '(max. 50): <br/><input type="text" name="' . $vote . '" value="' . htmlentities($_POST[$vote], ENT_QUOTES, 'UTF-8') . '"/><br/>';
        }
        echo '<input type="hidden" name="count_vote" value="' . abs(intval($_POST['count_vote'])) . '"/>';
        echo ($_POST['count_vote'] < 8) ? '<br/><input type="submit" name="plus" value="Доб. отв."/>' : '';
        echo $_POST['count_vote'] > 2 ? '<input type="submit" name="minus" value="Уд. посл."/><br/>' : '<br/>';
        echo '<p><input type="submit" name="submit" value="Отправить"/></p></form>';
        echo '<a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">назад</a>';
    }
}
else {
    header('location: ../index.php?err');
}

?>