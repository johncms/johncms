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

require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");

if ($dostmod == 1)
{
    require_once ('../incfiles/ban.php');
    $do = isset($_GET['do']) ? $_GET['do'] : '';
    switch ($do)
    {
        case 'clean':
            ////////////////////////////////////////////////////////////
            // Амнистия, запись в базу данных                         //
            ////////////////////////////////////////////////////////////
            if ($dostadm)
            {
                $amnesty = isset($_POST['amnesty']) ? intval($_POST['amnesty']) : '';
                switch ($amnesty)
                {
                    case 1:
                        // Амнистия
                        $req = mysql_query("SELECT * FROM `cms_ban_users` WHERE `ban_time` > '" . $realtime . "';");
                        while ($res = mysql_fetch_array($req))
                        {
                            $ban_left = $res['ban_time'] - $realtime;
                            if ($ban_left < 259200)
                            {
                                // Разбаниваем всех, кому осталось менее 3-х суток
                                mysql_query("UPDATE `cms_ban_users` SET `ban_time`='" . $realtime . "', `ban_raz`='--Амнистия--' WHERE `id`='" . $res['id'] . "';");
                            } elseif ($ban_left < 2592000)
                            {
                                // У кого бан менее 30 дней, уменьшаем срок в 2 раза
                                $ban_time = $res['ban_time'] - (($res['ban_time'] - $realtime) / 2);
                                mysql_query("UPDATE `cms_ban_users` SET `ban_time`='" . $ban_time . "', `ban_raz`='--Амнистия--' WHERE `id`='" . $res['id'] . "';");
                            }
                        }
                        break;
                    case 2:
                        // Разбаниваем активные
                        $req = mysql_query("SELECT * FROM `cms_ban_users` WHERE `ban_time` > '" . $realtime . "';");
                        while ($res = mysql_fetch_array($req))
                        {
                            $ban_left = $res['ban_time'] - $realtime;
                            if ($ban_left < 2592000)
                            {
                                mysql_query("UPDATE `cms_ban_users` SET `ban_time`='" . $realtime . "', `ban_raz`='--Амнистия--' WHERE `id`='" . $res['id'] . "';");
                            }
                        }
                        break;
                    case 3:
                        // Удаляем активные баны
                        mysql_query("DELETE FROM `cms_ban_users` WHERE `ban_time` > '" . $realtime . "';");
                        mysql_query("OPTIMIZE TABLE `cms_ban_users`;");
                        break;
                    case 4:
                        // Удаляем историю у неактивных банов
                        mysql_query("DELETE FROM `cms_ban_users` WHERE `ban_time` < '" . $realtime . "';");
                        mysql_query("OPTIMIZE TABLE `cms_ban_users`;");
                        break;
                    case 5:
                        // Полная очистка базы банов
                        mysql_query("TRUNCATE TABLE `cms_ban_users`;");
                        break;
                    default:
                        echo '<p>Ошибка</p>';
                        require_once ("../incfiles/end.php");
                        exit;
                }
                header("Location: zaban.php?do=amnesty&type=$amnesty&ok");
            }
            break;

        case 'amnesty':
            ////////////////////////////////////////////////////////////
            // Амнистия, форма выбора действий                        //
            ////////////////////////////////////////////////////////////
            if ($dostadm)
            {
                if (isset($_GET['ok']))
                {
                    echo '<p><b>Амнистия проведена успешно</b><br /><small>';
                    $type = isset($_GET['type']) ? intval($_GET['type']) : '';
                    switch ($type)
                    {
                        case 1:
                            echo 'Разбанены все, у кого оставшийся срок был менее 3-х суток<br />Уменьшен в 2 раза срок для тех, у кого осталось более 3-х суток.';
                            break;
                        case 2:
                            echo 'Разбанены все активные баны.';
                            break;
                        case 3:
                            echo 'Удалены все активные баны, и их записи из истории нарушений.';
                            break;
                        case 4:
                            echo 'Полностью удалена вся история нарушений для неактивных банов.';
                            break;
                        case 5:
                            echo 'База банов полностью очищена.<br />Удалены все активные баны и вся история нарушений.';
                            break;
                    }
                    echo '</small></p><p><a href="main.php">В админку</a></p>';
                } else
                {
                    echo '<div class="phdr">Амнистия</div>';
                    echo '<div class="menu"><form action="zaban.php?do=clean" method="post"><p>';
                    echo '<input type="radio" value="1" checked="checked" name="amnesty" />&nbsp;Амнистия<br />';
                    echo '<input type="radio" value="2" name="amnesty" />&nbsp;Разбанить активные<br />';
                    echo '<input type="radio" value="3" name="amnesty" />&nbsp;Удалить активные<br />';
                    echo '<input type="radio" value="4" name="amnesty" />&nbsp;Очистить историю<br />';
                    echo '<input type="radio" value="5" name="amnesty" />&nbsp;Очистить базу';
                    echo '</p>Внимание, отмена невозможна.<br />Вы уверены?<p><input type="submit" value="Амнистия" /></p><p><a href="main.php">В админку</a></p></form></div>';
                    echo '<div class="bmenu">';
                    echo '<p><b>Амнистия</b><br /><small>Разбанивает всех, у кого оставшийся срок менее 3-х суток<br />Уменьшает в 2 раза срок тем, у кого осталось более 3, но менее 30 суток.<br />Не влияет на бан "До отмены".<br />История нарушений сохраняется.</small></p>';
                    echo '<p><b>Разбанить активные</b><br /><small>Разбанивает все активные баны, кроме "До отмены"<br />История нарушений сохраняется.</small></p>';
                    echo '<p><b>Удалить активные</b><br /><small>Удаляет все активные баны, и их записи из истории нарушений.</small></p>';
                    echo '<p><b>Очистить историю</b><br /><small>Полностью удаляет всю историю нарушений для неактивных банов.<br />Не влияет на активные баны.</small></p>';
                    echo '<p><b>Очистить базу</b><br /><small>База банов полностью очищается.<br />Удаляются все активные баны и вся история нарушений.</small></p>';
                    echo '</div>';
                }
            }
            break;

        case 'razban':
            ////////////////////////////////////////////////////////////
            // Снятие бана с сохранением истории нарушений            //
            ////////////////////////////////////////////////////////////
            if (!empty($id))
            {
                if (mysql_num_rows(mysql_query("SELECT * FROM `cms_ban_users` WHERE `id`='" . $id . "';")) != 1)
                {
                    echo '<p>Ошибка<br /><a href="main.php">В админку</a></p>';
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if (isset($_GET['yes']))
                {
                    mysql_query("UPDATE `cms_ban_users` SET `ban_time`='" . $realtime . "', `ban_raz`='" . $login . "' WHERE `id`='" . $id . "';");
                    echo '<p>Действие Бана прекращено.<br /><a href="zaban.php">Бан-панель</a><br /><a href="main.php">В админку</a></p>';
                } else
                {
                    echo '<div class="phdr">Разбанить</div>';
                    echo '<p>1) Прекращается действие активного бана<br />2) Остается запись в истории нарушений.</p>';
                    echo '<p><b>Вы уверены?</b><br /><a href="zaban.php?do=razban&amp;id=' . $id . '&amp;yes=1">Разбанить</a><br /><a href="zaban.php?do=detail&amp;id=' . $id . '">Отмена</a></p>';
                }
            }
            break;

        case 'delban':
            ////////////////////////////////////////////////////////////
            // Снятие бана и удаление истории нарушений               //
            ////////////////////////////////////////////////////////////
            if (!empty($id))
            {
                if (mysql_num_rows(mysql_query("SELECT * FROM `cms_ban_users` WHERE `id`='" . $id . "';")) != 1)
                {
                    echo '<p>Ошибка<br /><a href="main.php">В админку</a></p>';
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if (isset($_GET['yes']))
                {
                    mysql_query("DELETE FROM `cms_ban_users` WHERE `id`='" . $id . "' LIMIT 1;");
                    echo '<p>Бан удален.<br /><a href="zaban.php">Бан-панель</a><br /><a href="main.php">В админку</a></p>';
                } else
                {
                    echo '<div class="phdr">Удалить Бан</div>';
                    echo '<p>1) Активный бан удаляется<br />2) Удаляется текущая запись из истории нарушений.</p>';
                    echo '<p><b>Вы уверены?</b><br /><a href="zaban.php?do=delban&amp;id=' . $id . '&amp;yes=1">Удалить Бан</a><br /><a href="zaban.php?do=detail&amp;id=' . $id . '">Отмена</a></p>';
                }
            }
            break;

        case 'detail':
            ////////////////////////////////////////////////////////////
            // Детали отдельного бана                                 //
            ////////////////////////////////////////////////////////////
            $req = mysql_query("SELECT `cms_ban_users`.*, `users`.`name`, `users`.`name_lat`
			FROM `cms_ban_users` LEFT JOIN `users` ON `cms_ban_users`.`user_id` = `users`.`id`
			WHERE `cms_ban_users`.`id`='" . $id . "';");
            if (mysql_num_rows($req) != 0)
            {
                $res = mysql_fetch_array($req);
                echo '<div class="phdr">Бан детально</div>';
                if (isset($_GET['ok']))
                    echo '<div class="rmenu">Юзер забанен</div>';
                echo '<div class="menu">Ник: <a href="../str/anketa.php?user=' . $res['user_id'] . '"><b>' . $res['name'] . '</b></a></div>';
                echo '<div class="menu">Тип бана: <b>' . $ban_term[$res['ban_type']] . '</b><br />';
                echo $ban_desc[$res['ban_type']] . '</div>';
                echo '<div class="menu">Забанил: ' . $res['ban_who'] . '</div>';
                echo '<div class="menu">Когда: ' . gmdate('d.m.Y, H:i:s', $res['ban_while']) . '</div>';
                echo '<div class="menu">Срок: ' . timecount($res['ban_time'] - $res['ban_while']) . '</div>';
                echo '<div class="bmenu">Причина</div>';
                if (!empty($res['ban_ref']))
                    echo '<div class="menu">Нарушение <a href="' . $home . '/forum/index.php?act=post&amp;id=' . $res['ban_ref'] . '">на форуме</a></div>';
                if (!empty($res['ban_reason']))
                    echo '<div class="menu">' . $res['ban_reason'] . '</div>';
                echo '<div class="bmenu">Осталось: ' . timecount($res['ban_time'] - $realtime) . '</div><p>';
                if ($dostadm == 1)
                {
                    echo '<a href="zaban.php?do=razban&amp;id=' . $id . '">Разбанить</a>';
                    echo '<br /><a href="zaban.php?do=delban&amp;id=' . $id . '">Удалить бан</a>';
                }
                echo '</p><p><a href="zaban.php">Бан-панель</a><br /><a href="main.php">В админку</a></p>';
            } else
            {
                echo 'Ошибка';
                require_once ("../incfiles/end.php");
                exit;
            }
            break;

        case 'help':
            echo '<div class="phdr">Справка по бану</div>';
            echo '<div class="menu">Все виды бана (кроме блокировки) разрешают пользователю доступ на сайт под его ником, но не позволяют писать.<br />';
            echo 'Есть возможность читать входящую почту, но при банах &quot;тишина&quot;, или &quot;приват&quot;, нельзя отправлять письма.</div><div class="menu">';
            echo '<b>' . $ban_term[1] . '</b><br />' . $ban_desc[1] . '<br />';
            echo '<b>' . $ban_term[3] . '</b><br />' . $ban_desc[3] . '<br />';
            echo '<b>' . $ban_term[10] . '</b><br />' . $ban_desc[10] . '<br />';
            echo '<b>' . $ban_term[11] . '</b><br />' . $ban_desc[11] . '<br />';
            echo '<b>Пинок</b><br />Разновидность бана по форуму. Специальная функция для модеров. Макс. срок 24 часа.<br />';
            echo '<b>' . $ban_term[12] . '</b><br />' . $ban_desc[12] . '<br />';
            echo '<b>' . $ban_term[13] . '</b><br />' . $ban_desc[13] . '<br />';
            echo '<b>' . $ban_term[14] . '</b><br />' . $ban_desc[14] . '<br />';
            echo '<b>' . $ban_term[9] . '</b><br />' . $ban_desc[9];
            echo '</div><div class="bmenu"><a href="zaban.php?do=ban&amp;id=' . $id . '">Назад</a></div>';
            break;

        case 'ban':
            if (!empty($id))
            {
                $req = mysql_query("SELECT * FROM `users` WHERE `id`='" . $id . "' LIMIT 1;");
                // Проверяем, есть ли в базе пользователь
                if (mysql_num_rows($req) != 1)
                {
                    echo 'Такого пользователя нет';
                    require_once ("../incfiles/end.php");
                    exit;
                }
                $res = mysql_fetch_array($req);
                // Достаточно ли прав, чтоб банить выбранного юзера
                if ($login != $nickadmina && $login != $nickadmina2 && ($res['name'] == $nickadmina || $res['name'] == $nickadmina2 || $res['rights'] >= $rights))
                {
                    echo '<p>У Вас недостаточно прав, чтоб банить этого пользователя.</p>';
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if ($res['immunity'] == 1)
                {
                    echo '<p>Этот юзер имеет иммунитет, банить его нельзя.</p>';
                    require_once ("../incfiles/end.php");
                    exit;
                }
                // Обработка принятых из формы данных
                if (isset($_POST['submit']))
                {
                    $term = isset($_POST['term']) ? intval($_POST['term']) : '';
                    $timeval = isset($_POST['timeval']) ? intval($_POST['timeval']) : '';
                    $time = isset($_POST['time']) ? intval($_POST['time']) : '';
                    $reason = !empty($_POST['reason']) ? check($_POST['reason']) : '';
                    $banref = isset($_POST['banref']) ? intval($_POST['banref']) : '';
                    if (empty($reason) && empty($banref))
                        $reason = 'Причина не указана';
                    if (empty($term) || empty($timeval) || empty($time) || $timeval < 1)
                    {
                        echo 'Отсутствуют необходимые данные';
                        require_once ("../incfiles/end.php");
                        exit;
                    }
                    // Проверяем, есть ли аналогичный активный бан
                    $banq = mysql_query("SELECT * FROM `cms_ban_users` WHERE `user_id`='" . $id . "' AND `ban_time`>'" . $realtime . "' AND `ban_type`='" . $term . "' LIMIT 1;");
                    if (mysql_num_rows($banq) == 1)
                    {
                        $banr = mysql_fetch_array($banq);
                        echo '<p><b>Внимание,</b><br />такой бан уже есть.<br /><a href="zaban.php?do=detail&amp;id=' . $banr['id'] . '">Смотреть</a><br /><a href="zaban.php?do=ban&amp;id=' . $id . '">Назад</a></p>';
                        require_once ("../incfiles/end.php");
                        exit;
                    }
                    // Пересчитываем время бана
                    switch ($time)
                    {
                        case 2:
                            // Часы
                            if ($timeval > 24)
                                $timeval = 24;
                            $timeval = $timeval * 3600;
                            break;
                        case 3:
                            // Дни
                            if ($timeval > 30)
                                $timeval = 30;
                            $timeval = $timeval * 86400;
                            break;
                        case 4:
                            // До отмены (на 10 лет)
                            $timeval = 315360000;
                            break;
                        default:
                            // Минуты
                            if ($timeval > 60)
                                $timeval = 60;
                            $timeval = $timeval * 60;
                    }
                    // Заносим в базу
                    mysql_query("INSERT INTO `cms_ban_users` SET
					`user_id`='" . $id . "',
					`ban_time`='" . ($realtime + $timeval) . "',
					`ban_while`='" . $realtime . "',
					`ban_type`='" . $term . "',
					`ban_who`='" . $login . "',
					`ban_ref`='" . $banref . "',
					`ban_reason`='" . $reason . "'
					;");
                    $detail = mysql_insert_id();
                    header("Location: zaban.php?do=detail&id=$detail&ok=1");
                } else
                {
                    // Форма ввода Бана
                    echo '<div class="phdr">Кого наказываем?</div>';
                    echo '<div class="gmenu">Ник: <a href="../str/anketa.php?user=' . $id . '"><b>' . $res['name'] . '</b></a>';
                    echo '</div><form action="zaban.php?do=ban&amp;id=' . $id . '" method="post">';
                    echo '<div class="rmenu"><b>Тип Бана:</b>&nbsp;<a href="zaban.php?do=help&amp;id=' . $id . '">[?]</a></div>';
                    if ($dostsmod == 1)
                    {
                        echo '<div class="menu"><input name="term" type="radio" value="1" checked="checked" />Тишина<br />';
                        echo '<input name="term" type="radio" value="3" />Приват<br />';
                        echo '<input name="term" type="radio" value="10" />Каменты<br />';
                        echo '<input name="term" type="radio" value="11" />Форум<br />';
                        echo '<input name="term" type="radio" value="12" />Чат<br />';
                        echo '<input name="term" type="radio" value="13" />Гостевая<br />';
                        echo '<input name="term" type="radio" value="14" />Галерея<br />';
                        if ($dostadm == 1)
                            echo '<input name="term" type="radio" value="9" /><b>блокировка</b>';
                    } elseif ($dostfmod == 1)
                    {
                        echo '<input name="term" type="hidden" value="11" />';
                        echo '<div class="menu">Пинок по форуму';
                    } elseif ($dostcmod == 1)
                    {
                        echo '<input name="term" type="hidden" value="12" />';
                        echo '<div class="menu">Пинок по чату';
                    }
                    echo '</div><div class="rmenu"><b>Срок Бана:</b></div>';
                    echo '<div class="menu"><input type="text" name="timeval" size="2" maxlength="2" value="10"/>&nbsp;время<br/>';
                    echo '<input name="time" type="radio" value="1" checked="checked" />минут (60 max)<br />';
                    echo '<input name="time" type="radio" value="2" />часов (24 max)<br />';
                    if ($dostsmod)
                        echo '<input name="time" type="radio" value="3" />дней (30 max)<br />';
                    if ($dostadm == 1)
                        echo '<input name="time" type="radio" value="4" /><b>до отмены</b>';
                    echo '</div><div class="rmenu"><b>Причина Бана:</b></div>';
                    if (isset($_GET['fid']))
                    {
                        // Если бан из форума, фиксируем ID поста
                        $fid = intval($_GET['fid']);
                        echo '<div class="menu">Нарушение <a href="' . $home . '/forum/index.php?act=post&amp;id=' . $fid . '">на форуме</a></div>';
                        echo '<input type="hidden" value="' . $fid . '" name="banref" />';
                    }
                    echo '<div class="menu"><textarea cols="20" rows="4" name="reason"></textarea></div>';
                    echo '<div class="bmenu"><input type="submit" name="submit" value="Банить"/></div>';
                    echo '</form>';
                    echo '<p><a href="../str/anketa.php?user=' . $id . '">Отмена</a></p>';
                }
            }
            break;

        default:
            ////////////////////////////////////////////////////////////
            // Список забаненных                                      //
            ////////////////////////////////////////////////////////////
            echo '<div class="phdr">Кто в бане?</div>';
            $req = mysql_query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `ban_time`>'" . $realtime . "'");
            $total = mysql_result($req, 0);
            if ($total > 0)
            {
                $req = mysql_query("SELECT `cms_ban_users`.*, `users`.`name`
				FROM `cms_ban_users` LEFT JOIN `users` ON `cms_ban_users`.`user_id` = `users`.`id`
				WHERE `cms_ban_users`.`ban_time`>'" . $realtime . "' LIMIT " . $start . "," . $kmess);
                // Выводим общий список забаненных
                while ($res = mysql_fetch_array($req))
                {
                    echo '<div class="menu"><a href="zaban.php?do=detail&amp;id=' . $res['id'] . '"><b>' . $res['name'] . '</b></a>&nbsp;';
                    echo $ban_term[$res['ban_type']];
                    echo '</div>';
                }
                echo '<div class="bmenu">Всего: ' . $total . '</div>';
                if ($total > $kmess)
                {
                    echo '<p>' . pagenav('zaban.php?', $start, $total, $kmess) . '</p>';
                    echo '<p><form action="zaban.php" method="get"><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
                }
                echo '<p><a href="main.php?do=search">Банить</a><br />';
                if ($dostadm)
                    echo '<a href="zaban.php?do=amnesty">Амнистия</a>';
            } else
            {
                echo '<p>Забаненных нет.';
            }
            echo '</p><p><a href="main.php">В админку</a></p>';
    }
} else
{
    header("Location: ../index.php?mod=404");
}

require_once ("../incfiles/end.php");

?>