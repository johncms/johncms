<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                                                                    //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@johncms.com                     //
// Олег Касьянов aka AlkatraZ          alkatraz@johncms.com                   //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_JOHNCMS', 1);

require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");

// Проверяем права доступа в Админ-Клуб
if (isset($_SESSION['ga']) && $dostmod != 1)
    unset($_SESSION['ga']);

// Задаем заголовки страницы
$headmod = 'guest';
$textl = isset($_SESSION['ga']) ? 'Админ-Клуб' : 'Гостевая';

// Если гостевая закрыта, выводим сообщение и закрываем доступ (кроме Админов)
if (!$set['mod_guest'] && $dostadm != 1)
{
    echo '<p>' . $set['mod_guest_msg'] . '</p>';
    require_once ("../incfiles/end.php");
    exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
switch ($act)
{
    case "delpost":
        ////////////////////////////////////////////////////////////
        // Удаление отдельного поста                              //
        ////////////////////////////////////////////////////////////
        if ($dostsmod == 1)
        {
            if (empty($_GET['id']))
            {
                echo "Ошибка!<br/><a href='guest.php?'>В гостевую</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $id = intval($_GET['id']);
            if (isset($_GET['yes']))
            {
                mysql_query("DELETE FROM `guest` WHERE `id`='" . $id . "' LIMIT 1;");
                header("Location: guest.php");
            } else
            {
                echo '<p>Вы действительно хотите удалить пост?<br/>';
                echo "<a href='guest.php?act=delpost&amp;id=" . $id . "&amp;yes'>Удалить</a> | <a href='guest.php'>Отмена</a></p>";
            }
        } else
        {
            echo "Доступ закрыт!!!<br/>";
        }
        break;

    case "trans":
        ////////////////////////////////////////////////////////////
        // Справка по транслиту                                   //
        ////////////////////////////////////////////////////////////
        include ("../pages/trans.$ras_pages");
        echo '<br/><br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
        break;

    case "say":
        ////////////////////////////////////////////////////////////
        // Добавление нового поста                                //
        ////////////////////////////////////////////////////////////
        if (empty($user_id) && empty($_POST['name']))
        {
            echo "<p>Вы не ввели имя!<br/><a href='guest.php'>Назад</a></p>";
            require_once ("../incfiles/end.php");
            exit;
        }
        if (empty($_POST['msg']))
        {
            echo "<p>Вы не ввели сообщение!<br/><a href='guest.php'>Назад</a></p>";
            require_once ("../incfiles/end.php");
            exit;
        }
        if (empty($_SESSION['guest']) || $ban['1'] || $ban['13'])
        {
            echo "<p><b>Спам!</b></p>";
            require_once ("../incfiles/end.php");
            exit;
        }
        if (!$user_id && $_SESSION['code'] != $_POST['code'])
        {
            echo "<p>Код введен неверно!<br/><a href='guest.php'>Назад</a></p>";
            require_once ("../incfiles/end.php");
            exit;
        }
        $agn = strtok($agn, ' ');
        // Задаем куда вставляем, в Админ клуб (1), или в Гастивуху (0)
        $admset = isset($_SESSION['ga']) ? 1:
        0;
        $req = mysql_query("SELECT * FROM `guest` WHERE `soft`='" . mysql_real_escape_string($agn) . "' AND `time` >='" . ($realtime - 30) . "' AND `ip` ='" . $ipl . "' AND `adm`='" . $admset . "';");
        if (mysql_num_rows($req) > 0)
        {
            echo "<p><b>Антифлуд!</b><br />Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><br/><a href='guest.php'>Назад</a></p>";
            require_once ("../incfiles/end.php");
            exit;
        }
        unset($_SESSION['guest']);
        $name = check(trim($_POST['name']));
        $name = mb_substr($name, 0, 25);
        if (!empty($_SESSION['uid']))
        {
            $from = $login;
        } else
        {
            $from = $name;
            $user_id = 0;
        }
        $msg = trim($_POST['msg']);
        $msg = mb_substr($msg, 0, 500);
        if ($_POST['msgtrans'] == 1)
        {
            $msg = trans($msg);
        }
        // Вставляем сообщение в базу
        mysql_query("INSERT INTO `guest` SET
		`adm`='" . $admset . "',
		`time`='" . $realtime . "',
		`user_id`='" . $user_id . "',
		`name`='" . mysql_real_escape_string($from) . "',
		`text`='" . mysql_real_escape_string($msg) . "',
		`ip`='" . $ipl . "',
		`soft`='" . mysql_real_escape_string($agn) . "';");
        header("location: guest.php");
        break;

    case "otvet":
        ////////////////////////////////////////////////////////////
        // Добавление "ответа Админа"                             //
        ////////////////////////////////////////////////////////////
        if ($dostsmod == 1)
        {
            if (empty($_GET['id']))
            {
                echo "Ошибка!<br/><a href='guest.php?'>В гостевую</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            if (isset($_POST['submit']))
            {
                $otv = mb_substr($_POST['otv'], 0, 500);
                mysql_query("update `guest` set
				`admin`='" . $login . "',
				`otvet`='" . mysql_real_escape_string($otv) . "',
				`otime`='" . $realtime . "'
				where id='" . $id . "';");
                header("location: guest.php");
            } else
            {
                $ps = mysql_query("select * from `guest` where id='" . $id . "';");
                $ps1 = mysql_fetch_array($ps);
                if (!empty($ps1['otvet']))
                {
                    echo "<br /><b>Внимание!<br />На этот пост уже ответили.</b><br/><br/>";
                }
                $text = htmlentities($ps1['text'], ENT_QUOTES, 'UTF-8');
                $otv = htmlentities($ps1['otvet'], ENT_QUOTES, 'UTF-8');
                echo "Пост в гостевой:<br /><b>$ps1[name]:</b> $text&quot;<br/><br/><form action='guest.php?act=otvet&amp;id=" . $id . "' method='post'>Ответ:<br/><textarea rows='3' name='otv'>$otv</textarea><br/><input type='submit' name='submit' value='Ok!'/><br/></form><a href='guest.php?'>В гостевую</a><br/>";
            }
        } else
        {
            echo "Доступ закрыт!!!<br/>";
        }
        break;

    case "edit":
        ////////////////////////////////////////////////////////////
        // Редактирование поста                                   //
        ////////////////////////////////////////////////////////////
        if ($dostsmod == 1)
        {
            if (empty($_GET['id']))
            {
                echo "Ошибка!<br/><a href='guest.php?'>В гостевую</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $id = intval($_GET['id']);
            if (isset($_POST['submit']))
            {
                $req = mysql_query("select `edit_count` from `guest` where `id`='" . $id . "';");
                $res = mysql_fetch_array($req);
                $edit_count = $res['edit_count'] + 1;
                $msg = mb_substr($_POST['msg'], 0, 500);
                mysql_query("update `guest` set
				`text`='" . mysql_real_escape_string($msg) . "',
				`edit_who`='" . $login . "',
				`edit_time`='" . $realtime . "',
				`edit_count`='" . $edit_count . "'
				where `id`='" . $id . "';");
                header("location: guest.php");
            } else
            {
                $ps = mysql_query("select * from `guest` where id='" . $id . "';");
                $ps1 = mysql_fetch_array($ps);
                $text = htmlentities($ps1['text'], ENT_QUOTES, 'UTF-8');
                echo "Редактировать пост:<br/><br/><form action='guest.php?act=edit&amp;id=" . $id . "' method='post'><textarea rows='3' name='msg'>$text</textarea><br/><input type='submit' name='submit' value='Ok!'/><br/></form><a href='guest.php?'>В гостевую</a><br/>";
            }
        } else
        {
            echo "Доступ закрыт!!!<br/>";
        }
        break;

    case 'clean':
        ////////////////////////////////////////////////////////////
        // Очистка Гостевой                                       //
        ////////////////////////////////////////////////////////////
        if ($dostadm == 1)
        {
            if (isset($_POST['submit']))
            {
                $cl = isset($_POST['cl']) ? intval($_POST['cl']) : '';
                switch ($cl)
                {
                    case '1':
                        // Чистим сообщения, старше 1 дня
                        if (isset($_SESSION['ga']))
                        {
                            mysql_query("DELETE FROM `guest` WHERE `adm`='1' AND `time`<='" . ($realtime - 86400) . "';");
                        } else
                        {
                            mysql_query("DELETE FROM `guest` WHERE `adm`='0' AND `time`<='" . ($realtime - 86400) . "';");
                        }
                        mysql_query("OPTIMIZE TABLE `guest`;");
                        echo '<p>Удалены все сообщения, старше 1 дня.</p><p><a href="guest.php">Вернуться</a></p>';
                        break;

                    case '2':
                        // Проводим полную очистку
                        if (isset($_SESSION['ga']))
                        {
                            mysql_query("DELETE FROM `guest` WHERE `adm`='1';");
                        } else
                        {
                            mysql_query("DELETE FROM `guest` WHERE `adm`='0';");
                        }
                        mysql_query("OPTIMIZE TABLE `guest`;");
                        echo '<p>Удалены все сообщения.</p><p><a href="guest.php">Вернуться</a></p>';
                        break;

                    default:
                        // Чистим сообщения, старше 1 недели
                        if (isset($_SESSION['ga']))
                        {
                            mysql_query("DELETE FROM `guest` WHERE `adm`='1' AND `time`<='" . ($realtime - 604800) . "';");
                        } else
                        {
                            mysql_query("DELETE FROM `guest` WHERE `adm`='0' AND `time`<='" . ($realtime - 604800) . "';");
                        }
                        mysql_query("OPTIMIZE TABLE `guest`;");
                        echo '<p>Все сообщения, старше 1 недели удалены из Гостевой.</p><p><a href="guest.php">В Гостевую</a></p>';
                }
            } else
            {
                echo '<p><b>Очистка сообщений</b></p>';
                echo '<u>Что чистим?</u>';
                echo '<form id="clean" method="post" action="guest.php?act=clean">';
                echo '<input type="radio" name="cl" value="0" checked="checked" />Старше 1 недели<br />';
                echo '<input type="radio" name="cl" value="1" />Старше 1 дня<br />';
                echo '<input type="radio" name="cl" value="2" />Очищаем все<br />';
                echo '<input type="submit" name="submit" value="Очистить" />';
                echo '</form>';
                echo '<p><a href="guest.php">Отмена</a></p>';
            }
        } else
        {
            header("location: guest.php");
        }
        break;

    case 'ga':
        ////////////////////////////////////////////////////////////
        // Переключение режима работы Гостевая / Админ-клуб       //
        ////////////////////////////////////////////////////////////
        if ($dostmod == 1)
        {
            if ($_GET['do'] == 'set')
            {
                $_SESSION['ga'] = 1;
                $textl = 'Админ-Клуб';
            } else
            {
                unset($_SESSION['ga']);
                $textl = 'Гостевая';
            }
        }

    default:
        ////////////////////////////////////////////////////////////
        // Отображаем Гостевую, или Админ клуб                    //
        ////////////////////////////////////////////////////////////
        if (!$set['mod_guest'])
            echo '<p><font color="#FF0000"><b>Гостевая закрыта!</b></font></p>';
        // Форма ввода нового сообщения
        if (($user_id || $set['gb'] != 0) && !$ban['1'] && !$ban['13'])
        {
            $_SESSION['guest'] = rand(1000, 9999);
            echo '<form action="guest.php?act=say" method="post">';
            if (!$user_id)
            {
                echo "Имя(max. 25):<br/><input type='text' name='name' maxlength='25'/><br/>";
            }
            echo 'Текст сообщения(max. 500):<br/><textarea cols="20" rows="2" name="msg"></textarea><br/>';
            if ($offtr != 1)
            {
                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
            }
            if (!$user_id)
            {
                // CAPTCHA для гостей
                $_SESSION['code'] = rand(1000, 9999);
                echo '<img src="../code.php" alt="Код"/><br />';
                echo '<input type="text" size="4" maxlength="4"  name="code"/>&nbsp;введите код<br /><br />';
            }
            echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/></form><br />";
        } else
        {
            echo "<p>Гостевая закрыта.</p>";
        }
        if (isset($_SESSION['ga']) && ($login == $nickadmina || $login == $nickadmina2 || $rights >= "1"))
        {
            // Запрос для Админ клуба
            echo '<b>АДМИН-КЛУБ</b><hr class="redhr" />';
            $req = mysql_query("SELECT `guest`.*, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`
			FROM `guest` LEFT JOIN `users` ON `guest`.`user_id` = `users`.`id` WHERE `guest`.`adm`='1' ORDER BY `time` DESC;");
        } else
        {
            // Запрос для обычной Гастивухи
            echo '<hr />';
            $req = mysql_query("SELECT `guest`.*, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`
			FROM `guest` LEFT JOIN `users` ON `guest`.`user_id` = `users`.`id` WHERE `guest`.`adm`='0' ORDER BY `time` DESC;");
        }
        $colmes = mysql_num_rows($req);
        if (empty($_GET['page']))
        {
            $page = 1;
        } else
        {
            $page = intval($_GET['page']);
        }
        $start = $page * 10 - 10;
        if ($colmes < $start + 10)
        {
            $end = $colmes;
        } else
        {
            $end = $start + 10;
        }
        while ($res = mysql_fetch_array($req))
        {
            if ($i >= $start && $i < $end)
            {
                $d = $i / 2;
                $d1 = ceil($d);
                $d2 = $d1 - $d;
                $d3 = ceil($d2);
                if ($d3 == 0)
                {
                    $div = "<div class='b'>";
                } else
                {
                    $div = "<div class='c'>";
                }
                echo $div;
                if ($res['user_id'] != "0")
                {
                    // Значок пола
                    switch ($res['sex'])
                    {
                        case "m":
                            echo '<img src="../images/m.gif" alt=""/>&nbsp;';
                            break;
                        case "zh":
                            echo '<img src="../images/f.gif" alt=""/>&nbsp;';
                            break;
                    }
                    // Ник юзера и ссылка на Анкету
                    if (!empty($user_id) && ($user_id != $res['user_id']))
                    {
                        echo '<a href="anketa.php?user=' . $res['user_id'] . '"><b>' . $res['name'] . '</b></a> ';
                    } else
                    {
                        echo '<b>' . $res['name'] . '</b>';
                    }
                    // Должность
                    switch ($res['rights'])
                    {
                        case 7:
                            echo ' Adm ';
                            break;
                        case 6:
                            echo ' Smd ';
                            break;
                        case 2:
                            echo ' Mod ';
                            break;
                        case 1:
                            echo ' Kil ';
                            break;
                    }
                    // Онлайн / Офлайн
                    $ontime = $res['lastdate'] + 300;
                    if ($realtime > $ontime)
                    {
                        echo '<font color="#FF0000"> [Off]</font>';
                    } else
                    {
                        echo '<font color="#00AA00"> [ON]</font>';
                    }
                } else
                {
                    // Ник Гостя
                    echo '<b>Гость ' . $res['name'] . '</b>';
                }
                $vrp = $res['time'] + $sdvig * 3600;
                $vr = date("d.m.y / H:i", $vrp);
                echo ' <font color="#999999">(' . $vr . ')</font><br/>';
                if (!empty($res['status']))
                    echo '<div class="status"><img src="../images/star.gif" alt=""/>&nbsp;'.$res['status'] . '</div>';
                $text = htmlentities($res['text'], ENT_QUOTES, 'UTF-8');
                if ($res['user_id'] != "0")
                {
                    // Для зарегистрированных показываем ссылки и смайлы
                    $text = tags($text);
                    $text = str_replace("\r\n", "<br />", $text);
                    if ($offsm != 1 && $offgr != 1)
                    {
                        $text = smiles($text);
                        $text = smilescat($text);
                        if ($res['name'] == nickadmina || $res['name'] == nickadmina2 || $res['rights'] >= 1)
                        {
                            $text = smilesadm($text);
                        }
                    }
                } else
                {
                    // Для гостей фильтруем ссылки
                    $text = antilink($text);
                }
                // Отображаем текст поста
                echo $text;
                // Если пост редактировался, показываем кто и когда
                if ($res['edit_count'] >= 1)
                {
                    $diz = $res['edit_time'] + $sdvig * 3600;
                    $dizm = date("d.m.y /H:i", $diz);
                    echo "<br /><small><font color='#999999'>Посл. изм. <b>$res[edit_who]</b>  ($dizm)<br />Всего изм.:<b> $res[edit_count]</b></font></small>";
                }
                // Ответ Модера
                if (!empty($res['otvet']))
                {
                    $otvet = htmlentities($res['otvet'], ENT_QUOTES, 'UTF-8');
                    $otvet = str_replace("\r\n", "<br />", $otvet);
                    $otvet = tags($otvet);
                    $vrp1 = $res['otime'] + $sdvig * 3600;
                    $vr1 = date("d.m.Y / H:i", $vrp1);
                    if ($offsm != 1 && $offgr != 1)
                    {
                        $otvet = smiles($otvet);
                        $otvet = smilescat($otvet);
                        $otvet = smilesadm($otvet);
                    }
                    echo '<div class="reply"><b>' . $res['admin'] . '</b>: (' . $vr1 . ')<br/>' . $otvet . '</div>';
                }
                // Ссылки на Модерские функции
                if ($dostsmod == 1)
                {
                    echo '<div class="func"><a href="guest.php?act=otvet&amp;id=' . $res['id'] . '">Отв.</a> | <a href="guest.php?act=edit&amp;id=' . $res['id'] . '">Изм.</a> | <a href="guest.php?act=delpost&amp;id=' . $res['id'] . '">Удалить</a><br/>';
                    echo long2ip($res['ip']) . ' - ' . $res['soft'] . '</div>';
                }
                echo "</div>";
            }
            ++$i;
        }
        echo isset($_SESSION['ga']) ? '<hr class="redhr" />':
        '<hr />';
        echo "<p>Всего сообщений: $colmes<br/>";
        if ($colmes > 10)
        {
            $ba = ceil($colmes / 10);
            if ($offpg != 1)
            {
                echo "Страницы: ";
            } else
            {
                echo "Страниц: $ba ";
            }
            $asd = $start - (10);
            $asd2 = $start + (10 * 2);

            if ($start != 0)
            {
                echo '<a href="guest.php?page=' . ($page - 1) . '">&lt;&lt;</a> ';
            }
            if ($offpg != 1)
            {
                if ($asd < $colmes && $asd > 0)
                {
                    echo ' <a href="guest.php?page=1&amp;">1</a> .. ';
                }
                $page2 = $ba - $page;
                $pa = ceil($page / 2);
                $paa = ceil($page / 3);
                $pa2 = $page + floor($page2 / 2);
                $paa2 = $page + floor($page2 / 3);
                $paa3 = $page + (floor($page2 / 3) * 2);
                if ($page > 13)
                {
                    echo ' <a href="guest.php?page=' . $paa . '">' . $paa . '</a> <a href="guest.php?page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="guest.php?page=' . ($paa * 2) . '">' . ($paa * 2) . '</a> <a href="guest.php?page=' . ($paa * 2 + 1) .
                        '">' . ($paa * 2 + 1) . '</a> .. ';
                } elseif ($page > 7)
                {
                    echo ' <a href="guest.php?page=' . $pa . '">' . $pa . '</a> <a href="guest.php?page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                }
                for ($i = $asd; $i < $asd2; )
                {
                    if ($i < $colmes && $i >= 0)
                    {
                        $ii = floor(1 + $i / 10);

                        if ($start == $i)
                        {
                            echo " <b>$ii</b>";
                        } else
                        {
                            echo ' <a href="guest.php?page=' . $ii . '">' . $ii . '</a> ';
                        }
                    }
                    $i = $i + 10;
                }
                if ($page2 > 12)
                {
                    echo ' .. <a href="guest.php?page=' . $paa2 . '">' . $paa2 . '</a> <a href="guest.php?page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="guest.php?page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="guest.php?page=' . ($paa3 + 1) .
                        '">' . ($paa3 + 1) . '</a> ';
                } elseif ($page2 > 6)
                {
                    echo ' .. <a href="guest.php?page=' . $pa2 . '">' . $pa2 . '</a> <a href="guest.php?page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                }
                if ($asd2 < $colmes)
                {
                    echo ' .. <a href="guest.php?page=' . $ba . '">' . $ba . '</a>';
                }
            } else
            {
                echo "<b>[$page]</b>";
            }
            if ($colmes > $start + 10)
            {
                echo ' <a href="guest.php?page=' . ($page + 1) . '">&gt;&gt;</a>';
            }
            echo '<br />';
        }
        // Для Админов даем ссылку на чистку Гостевой
        if ($dostadm == 1)
            echo '<a href="guest.php?act=clean">Чистка истории</a>';
        echo '</p>';
        // Для Модеров и выше, даем ссылку на Админ-клуб
        if ($dostmod == 1)
        {
            if (isset($_SESSION['ga']))
            {
                echo '<p><a href="guest.php?act=ga"><b>Гостевая</b></a></p>';
            } else
            {
                echo '<p><a href="guest.php?act=ga&amp;do=set"><b>Админ-Клуб</b></a></p>';
            }
        }
        break;
}

require_once ("../incfiles/end.php");

?>