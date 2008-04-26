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

$headmod = 'guest';
$textl = 'Гостевая';
require_once ("../incfiles/core.php");

if (!empty($_GET['act']))
{
    $act = check($_GET['act']);
}
switch ($act)
{
    case "delpost":
        if ($dostsmod == 1)
        {
            if (empty($_GET['id']))
            {
                require_once ("../incfiles/head.php");
                echo "Ошибка!<br/><a href='guest.php?'>В гостевую</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            if (isset($_GET['yes']))
            {
                mysql_query("delete from `guest` where `id`='" . $id . "' LIMIT 1;");
                header("Location: guest.php");
            } else
            {
                require_once ("../incfiles/head.php");
                echo '<p>Вы действительно хотите удалить пост?<br/>';
                echo "<a href='guest.php?act=delpost&amp;id=" . $id . "&amp;yes'>Удалить</a> | <a href='guest.php'>Отмена</a></p>";
            }
        } else
        {
            echo "Доступ закрыт!!!<br/>";
        }
        break;

    case "trans":
        require_once ("../incfiles/head.php");
        include ("../pages/trans.$ras_pages");
        echo '<br/><br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
        break;

    case "say":
        $agn = strtok($agn, ' ');
        $flt = $realtime - 30;
        $af = mysql_query("select * from `guest` where soft='" . $agn . "' and time >='" . $flt . "' and ip ='" . $ipl . "';");
        $af1 = mysql_num_rows($af);
        if ($af1 > 0)
        {
            require_once ("../incfiles/head.php");
            echo "<p><b>Антифлуд!</b><br />Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><br/><a href='guest.php'>Назад</a></p>";
            require_once ("../incfiles/end.php");
            exit;
        }
        if (empty($_POST['msg']) && empty($_POST['name']))
        {
            require_once ("../incfiles/head.php");
            echo "Вы не ввели имя!<br/><a href='guest.php'>Назад</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
        if (empty($_POST['msg']))
        {
            require_once ("../incfiles/head.php");
            echo "Вы не ввели сообщение!<br/><a href='guest.php'>Назад</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
        if (empty($_SESSION['guest']))
        {
            require_once ("../incfiles/head.php");
            echo "Спам!<br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
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
        mysql_query("insert into `guest` set
		`time`='" . $realtime . "',
		`user_id`='" . $user_id . "',
		`name`='" . mysql_real_escape_string($from) . "',
		`text`='" . mysql_real_escape_string($msg) . "',
		`ip`='" . $ipl . "',
		`soft`='" . mysql_real_escape_string($agn) . "';");
        header("location: guest.php");
        break;

    case "otvet":
        if ($dostsmod == 1)
        {
            if (empty($_GET['id']))
            {
                require_once ("../incfiles/head.php");
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
                require_once ("../incfiles/head.php");
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
        if ($dostsmod == 1)
        {
            if (empty($_GET['id']))
            {
                require_once ("../incfiles/head.php");
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
                require_once ("../incfiles/head.php");
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
        if ($dostadm == 1)
        {
            if (isset($_POST['submit']))
            {
                $cl = isset($_POST['cl']) ? intval($_POST['cl']) : '';
                switch ($cl)
                {
                    case '1':
                        mysql_query("delete from `guest` where `time`<='" . ($realtime - 86400) . "';");
                        mysql_query("OPTIMIZE TABLE `guest`;");
                        require_once ("../incfiles/head.php");
                        echo '<p>Все сообщения, старше 1 дня удалены из Гостевой.</p><p><a href="guest.php">В Гостевую</a></p>';
                        break;

                    case '2':
                        mysql_query("TRUNCATE TABLE `guest`;");
                        require_once ("../incfiles/head.php");
                        echo '<p>Гостевая полностью очищена.</p><p><a href="guest.php">В Гостевую</a></p>';
                        break;

                    default:
                        mysql_query("delete from `guest` where `time`<='" . ($realtime - 604800) . "';");
                        mysql_query("OPTIMIZE TABLE `guest`;");
                        require_once ("../incfiles/head.php");
                        echo '<p>Все сообщения, старше 1 недели удалены из Гостевой.</p><p><a href="guest.php">В Гостевую</a></p>';
                }
            } else
            {
                require_once ("../incfiles/head.php");
                echo '<p><b>Очистка Гостевой</b></p>';
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

    default:
        require_once ("../incfiles/head.php");
        $_SESSION['guest'] = rand(1000, 9999);
        if ((!empty($_SESSION['uid'])) || $gb != 0)
        {
            echo '<form action="guest.php?act=say" method="post">';
            if (empty($_SESSION['uid']))
            {
                echo "Имя(max. 25):<br/><input type='text' name='name' maxlength='25'/><br/>";
            }
            echo "Текст сообщения(max. 500):<br/><textarea cols='20' rows='2' title='Введите текст сообщения' name='msg'></textarea><br/>";
            if ($offtr != 1)
            {
                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
            }
            echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/></form><br /><hr/>";
        } else
        {
            echo "Гостевая временно закрыта для добавления сообщений.<br/>";
        }
        $req = mysql_query("SELECT `guest`.*, `users`.`rights`, `users`.`lastdate`, `users`.`sex`
		FROM `guest` LEFT JOIN `users` ON `guest`.`user_id` = `users`.`id` order by time desc ;");
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
                    switch ($res['sex'])
                    {
                        case "m":
                            echo '<img src="../images/m.gif" alt=""/>&nbsp;';
                            break;
                        case "zh":
                            echo '<img src="../images/f.gif" alt=""/>&nbsp;';
                            break;
                    }
                    if ((!empty($_SESSION['uid'])) && ($_SESSION['uid'] != $res['user_id']))
                    {
                        echo '<a href="anketa.php?user=' . $res['user_id'] . '"><b>' . $res['name'] . '</b></a> ';
                    } else
                    {
                        echo '<b>' . $res['name'] . '</b>';
                    }
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
                    $ontime = $res['lastdate'];
                    $ontime2 = $ontime + 300;
                    if ($realtime > $ontime2)
                    {
                        echo '<font color="#FF0000"> [Off]</font>';
                    } else
                    {
                        echo '<font color="#00AA00"> [ON]</font>';
                    }
                } else
                {
                    echo '<b>Гость ' . $res['name'] . '</b>';
                }
                $vrp = $res['time'] + $sdvig * 3600;
                $vr = date("d.m.y / H:i", $vrp);
                echo ' <font color="#999999">(' . $vr . ')</font><br/>';
                $text = htmlentities($res['text'], ENT_QUOTES, 'UTF-8');
                if ($res['user_id'] != "0")
                {
                    $text = texttolink($text);
                    $text = str_replace("\r\n", "<br />", $text);
                }
                if ($offsm != 1 && $offgr != 1)
                {
                    $text = smiles($text);
                    $text = smilescat($text);
                }
                if ($res['name'] == nickadmina || $res['name'] == nickadmina2 || $res['rights'] >= 1)
                {
                    if ($offsm != 1 && $offgr != 1)
                        $text = smilesadm($text);
                } else
                {
                    $text = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "***", $text);
                    $text = antilink($text);
                }
                echo $text;


                if ($res['edit_count'] >= 1)
                {
                    $diz = $res['edit_time'] + $sdvig * 3600;
                    $dizm = date("d.m.y /H:i", $diz);
                    echo "<br /><small><font color='#999999'>Посл. изм. <b>$res[edit_who]</b>  ($dizm)<br />Всего изм.:<b> $res[edit_count]</b></font></small>";
                }

                if ($dostsmod == 1)
                {
                    echo "<br /><a href='guest.php?act=otvet&amp;id=" . $res['id'] . "'>Отв.</a> | <a href='guest.php?act=edit&amp;id=" . $res['id'] . "'>Изм.</a> | <a href='guest.php?act=delpost&amp;id=" . $res['id'] . "'>Удалить</a><br/>";
                    echo long2ip($res['ip']) . ' - ' . $res['soft'];
                }
                if ($res['otvet'] != '')
                {
                    $otvet = htmlentities($res['otvet'], ENT_QUOTES, 'UTF-8');
                    $otvet = str_replace("\r\n", "<br />", $otvet);
                    $vrp1 = $res['otime'] + $sdvig * 3600;
                    $vr1 = date("d.m.Y / H:i", $vrp1);
                    $otvet = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $otvet);
                    $otvet = eregi_replace("\\[l\\]([[ : alnum : ]_ = / : -] + (\\ . [[ : alnum : ]_ = / -] + ) * ( / [[ : alnum : ] + & . _ = / ~ % ] * (\\ ? [[ : alnum : ] ? + . &_ = /; % ] * ) ? ) ? )\\[l / \\](( . * ) ? )\\[ / l\
                        \]", " < a href = 'http://\\1' > \\6 < / a > ", $otvet);
                    if (stristr($otvet, " < a href = "))
                    {
                        $otvet = eregi_replace("\\ < a href\\ = '((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)' > [[ : alnum : ]_ = / -] + (\\ . [[ : alnum : ]_ = / -] + ) * ( / [[ :
                        alnum : ] + & . _ = / ~ % ] * (\\ ? [[ : alnum : ] ? + &_ = /; % ] * ) ? ) ? ) < / a > ", " < a href = '\\1\\3' > \\3 < / a > ", $otvet);
                    } else
                    {
                        $otvet = eregi_replace("((https ? | ftp) : //)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $otvet);
                    }
                    if ($offsm != 1 && $offgr != 1)
                    {
                        $otvet = smiles($otvet);
                        $otvet = smilescat($otvet);
                        $otvet = smilesadm($otvet);
                    }
                    echo '<br /><font color="#FF3333"><b>' . $res['admin'] . '</b>: (' . $vr1 . ')<br/>' . $otvet . '</font>';
                }
                echo "</div>";
            }
            ++$i;
        }
        echo "<hr/><p>Всего сообщений: $colmes<br/>";
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
        if ($dostadm == 1)
            echo '<a href="guest.php?act=clean">Чистка гостевой</a>';
        echo '</p>';
        break;
}

require_once ("../incfiles/end.php");

?>