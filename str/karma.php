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
// Модуль Кармы от FlySelf 
*/

define('_IN_JOHNCMS', 1);
$headmod = 'karma';
$textl = 'Карма юзера';
require_once('../incfiles/core.php');
require_once('../incfiles/head.php');
if ($set_karma['on'] && $user_id) {
    switch ($act) {
        case 'user':
            if (!$datauser['karma_off']) {
                $error = array ();
                $req = mysql_query("SELECT `ip`, `name`, `karma`, `plus_minus`, `rights` FROM `users` WHERE `id` = '$id' LIMIT 1");
                if (!mysql_num_rows($req) || $id == $user_id)
                    $error[] = 'Пользователь не найден';
                if (!$error) {
                    $res = mysql_fetch_assoc($req);
                    if ($res['rights'] && $set_karma['adm'])
                        $error[] = 'За администрацию голосовать запрещено';
                    if ($res['ip'] == $datauser['ip'])
                        $error[] = 'Накрутка кармы запрещена';
                    if ($datauser['total_on_site'] < $set_karma['karma_time'] || $datauser['postforum'] < $set_karma['forum'])
                        $error[] = 'В голосовании могут принимать участие только пользователи, пробывшие на сайте не менее '
                            . ($set_karma['time'] ? ($set_karma['karma_time'] / 3600) . ' час.' : ($set_karma['karma_time'] / 86400) . ' дн.') . ' и набравших на форуме ' . $set_karma['forum'] . ' пост.';
                    $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `karma_users` WHERE `user_id` = '$user_id' AND `karma_user` = '$id' AND `time` > '"
                        . ($realtime - 86400) . "'"), 0);
                    if ($count)
                        $error[] = 'За пользователя можно отдавать голос раз в 24 часа';
                    $sum = mysql_result(mysql_query("SELECT SUM(`points`) FROM `karma_users` WHERE `user_id` = '$user_id' AND `time` >= '"
                        . $datauser['karma_time'] . "'"), 0);
                    if (($set_karma['karma_points'] - $sum) <= 0)
                        $error[] = 'Лимит голосов на сегодня исчерпан. Голоса будут начислены '
                            . date('d.m.y в H:i:s', ($datauser['karma_time'] + 86400));
                }
                if ($error) {
                    $error[] = '<a href="anketa.php?id=' . $id . '">Вернуться</a>';
                    echo display_error($error);
                } else {
                    if (isset($_POST['submit'])) {
                        $text = trim($_POST['text']);
                        $type = intval($_POST['type']) ? 1 : 0;
                        $points = abs(intval($_POST['points']));
                        if (!$points || $points > ($set_karma['karma_points'] - $sum))
                            $points = 1;
                        $text = mysql_real_escape_string(mb_substr($text, 0, 500));
                        mysql_query("INSERT INTO `karma_users` SET `user_id` = '$user_id', `name` = '$login', `karma_user` = '$id', `points` = '$points', `type` = '$type', `time` = '$realtime', `text` = '$text'");
                        $plm = explode('|', $res['plus_minus']);
                        if ($type) {
                            $karma = $res['karma'] + $points;
                            $plm[0] = $plm[0] + $points;
                        } else {
                            $karma = $res['karma'] - $points;
                            $plm[1] = $plm[1] + $points;
                        }
                        $plus_minus = $plm[0] . '|' . $plm[1];
                        mysql_query("UPDATE `users` SET `karma`='$karma', `plus_minus`='$plus_minus' WHERE `id` = '$id' LIMIT 1");
                        echo '<div class="gmenu">Выполнено!<br /><a href="anketa.php?id=' . $id . '">Продолжить</a></div>';
                    } else {
                        echo '<div class="phdr"><b>Отдаем голос за ' . $res['name'] . '</b></div><form action="karma.php?act=user&amp;id=' . $id
                            . '" method="post"><div class="gmenu"><b>Тип голоса:</b><br />
                            <input name="type" type="radio" value="1" checked="checked"/> Положительный<br /><input name="type" type="radio" value="0"/> Отрицательный<br /><b>Количество голосов:</b><br /><select size="1" name="points">';
                        for ($i = 1; $i < ($set_karma['karma_points'] - $sum + 1); $i++) {
                            echo '<option value="' . $i . '">' . $i . '</option>';
                        }
                        echo
                            '</select><b><br />Комментарий:</b><br /><input name="text" type="text" value=""/><br /><small>максимум 500 символов</small><p><input type="submit" name="submit" value="Голосовать"/></p></div></form><div class="list2"><a href="anketa.php?id='
                            . $id . '">Анкета пользователя</a></div>';
                    }
                }
            } else {
                echo display_error('Вам запрещено отдавать голоса за пользователей<br /><a href="anketa.php?id=' . $id . '">Вернуться</a>');
            }
            break;

        case 'delete':
            if ($rights == 9) {
                $type = isset($_GET['type']) ? abs(intval($_GET['type'])) : NULL;
                $del = isset($_GET['del']) ? intval($_GET['del']) : NULL;
                $req = mysql_query("SELECT * FROM `karma_users` WHERE `id`='$del' AND `karma_user` = '$id' LIMIT 1");
                if (mysql_num_rows($req)) {
                    if (isset($_GET['yes'])) {
                        $res = mysql_fetch_assoc($req);
                        $user = mysql_fetch_assoc(mysql_query("SELECT `karma`, `plus_minus` FROM `users` WHERE `id` = '$id' LIMIT 1"));
                        $plm = explode('|', $user['plus_minus']);
                        if ($res['type']) {
                            $karma = $user['karma'] - $res['points'];
                            $plus_minus = ($plm[0] - $res['points']) . '|' . $plm[1];
                        } else {
                            $karma = $user['karma'] + $res['points'];
                            $plus_minus = $plm[0] . '|' . ($plm[1] - $res['points']);
                        }
                        mysql_query("DELETE FROM `karma_users` WHERE `id` = '$del' LIMIT 1");
                        mysql_query("UPDATE `users` SET `karma`='$karma', `plus_minus`='$plus_minus' WHERE `id` = '$id' LIMIT 1");
                        header('Location: karma.php?id=' . $id . '&type=' . $type);
                    } else {
                        echo '<p>Вы действительно хотите удалить отзыв?<br/>';
                        echo '<a href="karma.php?act=delete&amp;id=' . $id . '&amp;del='.$del.'&amp;type=' . $type . '&amp;yes">Удалить</a> | <a href="karma.php?id=' . $id . '&amp;type=' . $type . '">Отмена</a></p>';
                    }
                }
            }
            break;

        case 'clean':
            if ($id && $rights == 9) {
                if (isset($_GET['yes'])) {
                    mysql_query("DELETE FROM `karma_users` WHERE `karma_user`='$id'");
                    mysql_query("UPDATE `users` SET `karma`='0', `plus_minus`='0|0' WHERE `id` = '$id' LIMIT 1");
                    header('Location: karma.php?id=' . $id);
                } else {
                    echo '<p>Вы действительно хотите удалить все отзывы о пользователи?<br/>';
                    echo '<a href="karma.php?act=clean&amp;id=' . $id . '&amp;yes">Удалить</a> | <a href="karma.php?id='
                        . $id . '">Отмена</a></p>';
                }
            }
            break;

        case 'new':
            echo '<div class="phdr"><b>Новые отзывы</b></div>';
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '$user_id' AND `time` > "
                . ($realtime - 86400)), 0);
            if ($total) {
                $req = mysql_query("SELECT * FROM `karma_users` WHERE `karma_user`='$user_id' AND `time` > "
                    . ($realtime - 86400) . " ORDER BY `time` DESC LIMIT $start, $kmess");
                while ($res = mysql_fetch_assoc($req)) {
                    echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
                    echo $res['type'] ? '<span class="green">+' . $res['points'] . '</span> ' : '<span class="red">-' . $res['points'] . '</span> ';
                    echo $user_id == $res['user_id'] || !$res['user_id'] ? '<b>' . $res['name'] . '</b>' : '<a href="anketa.php?id=' . $res['user_id'] . '"><b>' . $res['name'] . '</b></a>';
                    echo ' <span class="gray">(' . date("d.m.y / H:i", $res['time'] + $set_user['sdvig'] * 3600) . ')</span>';
                    if (!empty($res['text']))
                        echo '<div class="sub">' . checkout($res['text']) . '</div>';
                    echo '</div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu">Список пуст</div>';
            }
            echo '<div class="phdr">Новых отзывов: ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<p>' . pagenav('karma.php?act=new&amp;', $start, $total, $kmess) . '</p>';
                echo '<p><form action="karma.php" method="get"><input type="hidden" name="act" value="new"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
            }
            echo '<div class="list2"><a href="anketa.php?">Моя анкета</a></div>';
            break;

        default:
            if ($id && $id != $user_id) {
                $req = mysql_query("SELECT `name`, `karma`, `plus_minus` FROM `users` WHERE `id` = '$id' LIMIT 1");
                if (!mysql_num_rows($req)) {
                    echo display_error('Пользователь не найден');
                    require_once('../incfiles/end.php');
                    exit;
                }
                $user = mysql_fetch_assoc($req);
            } else {
                $user = $datauser;
                $id = $user_id;
            }
            $exp = explode('|', $user['plus_minus']);
            echo '<p>';
            $type = abs(intval($_GET['type']));
            $sql = '';
            switch ($type) {
                case 2:
                    echo '<a href="karma.php?id=' . $id . '&amp;type=0">Все</a> | <a href="karma.php?id=' . $id . '&amp;type=1">Положительные</a> | Отрицательные';
                    $sql = ' AND `type` = 0';
                    break;

                case 1:
                    echo '<a href="karma.php?id=' . $id . '&amp;type=0">Все</a> | Положительные | <a href="karma.php?id=' . $id . '&amp;type=2">Отрицательные</a>';
                    $sql = ' AND `type` = 1';
                    break;

                default:
                    echo 'Все | <a href="karma.php?id=' . $id . '&amp;type=1">Положительные</a> | <a href="karma.php?id=' . $id . '&amp;type=2">Отрицательные</a>';
                    $type = 0;
            }
            echo '</p><div class="phdr"><b>Карма ' . $user['name'] . '</b> ' . $user['karma'] . ' (<span class="green">'
                . $exp[0] . '</span>/<span class="red">' . $exp[1] . '</span>)</div>';
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user`='$id' $sql"), 0);
            if ($total) {
                $req = mysql_query("SELECT * FROM `karma_users` WHERE `karma_user`='$id' $sql ORDER BY `time` DESC LIMIT $start, $kmess");
                while ($res = mysql_fetch_assoc($req)) {
                    echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
                    echo $res['type'] ? '<span class="green">+' . $res['points'] . '</span> ' : '<span class="red">-' . $res['points'] . '</span> ';
                    echo $user_id == $res['user_id'] || !$res['user_id'] ? '<b>' . $res['name'] . '</b>' : '<a href="anketa.php?id=' . $res['user_id'] . '"><b>' . $res['name'] . '</b></a>';
                    echo ' <span class="gray">(' . date("d.m.y / H:i", $res['time'] + $set_user['sdvig'] * 3600) . ')</span>';
                    if ($rights == 9)
                        echo ' <span class="red"><a href="karma.php?act=delete&amp;id=' . $id . '&amp;del=' . $res['id'] . '&amp;type=' . $type . '">[X]</a></span>';
                    if (!empty($res['text']))
                        echo '<div class="sub">' . checkout($res['text']) . '</div>';
                    echo '</div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu">Список пуст</div>';
            }
            echo '<div class="phdr">Всего отзывов: ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<p>' . pagenav('karma.php?id=' . $id . '&amp;type=' . $type . '&amp;', $start, $total, $kmess) . '</p>';
                echo '<p><form action="karma.php" method="get"><input type="hidden" name="id" value="' . $id . '"/><input type="hidden" name="type" value="' . $type
                    . '"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
            }
            if ($rights == 9)
                echo '<div class="func"><a href="karma.php?id=' . $id . '&amp;act=clean">Сбросить карму</a></div>';
            echo '<p><a href="anketa.php?id=' . $id . '">Анкета пользователя</a></p>';
    }
}

require_once('../incfiles/end.php');
?>