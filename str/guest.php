<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC1                                                        //
// Дата релиза: 08.02.2008                                                    //
// Авторский сайт: http://gazenwagen.com                                      //
////////////////////////////////////////////////////////////////////////////////
// Оригинальная идея и код: Евгений Рябинин aka JOHN77                        //
// E-mail: 
// Модификация, оптимизация и дизайн: Олег Касьянов aka AlkatraZ              //
// E-mail: alkatraz@batumi.biz                                                //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
// Внимание!                                                                  //
// Авторские версии данных скриптов публикуются ИСКЛЮЧИТЕЛЬНО на сайте        //
// http://gazenwagen.com                                                      //
// Если Вы скачали данный скрипт с другого сайта, то его работа не            //
// гарантируется и поддержка не оказывается.                                  //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_PUSTO', 1);


$headmod = 'guest';
$textl = 'Гостевая';
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");
require ("../incfiles/char.php");

function antilink($str)
{
    $str = strtr($str, array(".ru" => "***", ".com" => "***", ".net" => "***", ".org" => "***", ".info" => "***", ".mobi" => "***", ".wen" => "***", ".kmx" => "***", ".h2m" => "***"));
    return $str;
}

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
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
                echo "Ошибка!<br/><a href='guest.php?'>В гостевую</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            if (isset($_GET['yes']))
            {
                mysql_query("delete from `guest` where `id`='" . $id . "';");
                header("Location: guest.php");
            } else
            {
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
                echo "Вы действительно хотите удалить пост?<br/>";
                echo "<a href='guest.php?act=delpost&amp;id=" . $id . "&amp;yes'>Удалить</a>|<a href='guest.php'>Отмена</a><br/>";
            }
        } else
        {
            echo "Доступ закрыт!!!<br/>";
        }
        break;
        ################################
    case "trans":
        require ("../incfiles/head.php");
        require ("../incfiles/inc.php");
        include ("../pages/trans.$ras_pages");
        echo '<br/><br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
        break;
        ############################
    case "say":
        if (getenv("HTTP_CLIENT_IP"))
            $ipp = getenv("HTTP_CLIENT_IP");
        else
            if (getenv("REMOTE_ADDR"))
                $ipp = getenv("REMOTE_ADDR");
            else
                if (getenv("HTTP_X_FORWARDED_FOR"))
                    $ipp = getenv("HTTP_X_FORWARDED_FOR");
                else
                {
                    $ipp = "not detected";
                }
                $ipp = check($ipp);
        $agn = check(getenv(HTTP_USER_AGENT));
        $agn = strtok($agn, ' ');
        $flt = $realtime - 30;
        $af = mysql_query("select * from `guest` where soft='" . $agn . "' and time >='" . $flt . "' and ip ='" . $ipp . "';");
        $af1 = mysql_num_rows($af);
        if ($af1 > 0)
        {
            require ("../incfiles/head.php");
            require ("../incfiles/inc.php");
            echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><a href='guest.php'>Назад</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        if (empty($_POST['msg']) && empty($_POST['name']))
        {
            require ("../incfiles/head.php");
            require ("../incfiles/inc.php");
            echo "Вы не ввели имя!<br/><a href='guest.php'>Назад</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        if (empty($_POST['msg']))
        {
            require ("../incfiles/head.php");
            require ("../incfiles/inc.php");
            echo "Вы не ввели сообщение!<br/><a href='guest.php'>Назад</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        if (empty($_SESSION['guest']))
        {
            require ("../incfiles/head.php");
            require ("../incfiles/inc.php");
            echo "Спам!<br/>";
            require ("../incfiles/end.php");
            exit;
        }
        $name = check(trim($_POST['name']));
        $name = utfwin($name);
        $name = substr($name, 0, 500);
        $name = winutf($name);
        if (!empty($_SESSION['pid']))
        {
            $from = $login;
            $gs = 0;
        } else
        {
            $from = $name;
            $gs = 1;
        }

        $msg = check(trim($_POST['msg']));
        $msg = utfwin($msg);
        $msg = substr($msg, 0, 500);
        if ($o >= 496)
        {
            $o = strrpos($msg, "<");
            $msg = substr($msg, 0, $o);
        }
        $msg = winutf($msg);
        if ($_POST[msgtrans] == 1)
        {
            $msg = trans($msg);
        }

        mysql_query("insert into `guest` values(0,'" . $realtime . "','" . $from . "','" . $msg . "','" . $ipp . "','" . $agn . "','" . $gs . "','','','');");
        header("location: guest.php");
        break;
        #################
    case "otvet":
        if ($dostsmod == 1)
        {
            if (empty($_GET['id']))
            {
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
                echo "Ошибка!<br/><a href='guest.php?'>В гостевую</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            if (isset($_POST['submit']))
            {
                $otv = check($_POST['otv']);
                mysql_query("update `guest` set  admin='" . $login . "',otvet='" . $otv . "',otime='" . $realtime . "' where id='" . $id . "';");
                header("location: guest.php");
            } else
            {
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
                $ps = mysql_query("select * from `guest` where id='" . $id . "';");
                $ps1 = mysql_fetch_array($ps);
                if (!empty($ps1[otvet]))
                {
                    echo "На этот пост уже ответили!<br/><br/>";
                }
                echo "Пост в гостевой:&quot;$ps1[name]: $ps1[text]&quot;<br/><br/><form action='guest.php?act=otvet&amp;id=" . $id . "' method='post'>Ответ:<br/><textarea rows='3' name='otv'>$ps1[otvet]</textarea><br/><input type='submit' name='submit' value='Ok!'/><br/></form><a href='guest.php?'>В гостевую</a><br/>";
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
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
                echo "Ошибка!<br/><a href='guest.php?'>В гостевую</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            if (isset($_POST['submit']))
            {
                $msg = check($_POST['msg']);
                mysql_query("update `guest` set text='" . $msg . "' where id='" . $id . "';");
                header("location: guest.php");
            } else
            {
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
                $ps = mysql_query("select * from `guest` where id='" . $id . "';");
                $ps1 = mysql_fetch_array($ps);

                echo "Редактировать пост:<br/><br/><form action='guest.php?act=edit&amp;id=" . $id . "' method='post'><textarea rows='3' name='msg'>$ps1[text]</textarea><br/><input type='submit' name='submit' value='Ok!'/><br/></form><a href='guest.php?'>В гостевую</a><br/>";
            }
        } else
        {
            echo "Доступ закрыт!!!<br/>";
        }
        break;

    default:
        require ("../incfiles/head.php");
        require ("../incfiles/inc.php");
        $_SESSION['guest'] = rand(1000, 9999);
        if ((!empty($_SESSION['pid'])) || $gb != 0)
        {
            echo "<form action='guest.php?act=say' method='post'>";
            if (empty($_SESSION['pid']))
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
        $q1 = mysql_query("select * from `guest` order by time desc ;");
        $colmes = mysql_num_rows($q1);
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
        while ($mass = mysql_fetch_array($q1))
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
                if ($mass[gost] != "1")
                {
                    $uz = @mysql_query("select * from `users` where name='" . $mass[name] . "';");
                    $mass1 = @mysql_fetch_array($uz);
                }
                echo "$div";
                if ($pfon == 1)
                {
                    echo "<div style='background:" . $cpfon . ";'>";
                }
                if ($mass[gost] != "1")
                {
                    switch ($mass1[sex])
                    {
                        case "m":
                            echo "<img src='../images/m.gif' alt=''/>";
                            break;
                        case "zh":
                            echo "<img src='../images/f.gif' alt=''/>";
                            break;
                    }
                }
                if ($mass[gost] != "1")
                {
                    if ((!empty($_SESSION['pid'])) && ($_SESSION['pid'] != $mass1[id]))
                    {
                        echo "<a href='anketa.php?user=" . $mass1[id] . "'><b><font color='" . $conik . "'>$mass[name]</font></b></a> ";
                    } else
                    {
                        echo "<b><font color='" . $csnik . "'>$mass[name]</font></b>";
                    }
                } else
                {
                    echo "<b><font color='" . $cdinf . "'>Гость </font><font color='" . $conik . "'>$mass[name]</font></b>";
                }
                $vrp = $mass[time] + $sdvig * 3600;
                $vr = date("d.m.Y / H:i", $vrp);
                if ($mass[gost] != "1")
                {
                    switch ($mass1[rights])
                    {
                        case 7:
                            echo "<font color='" . $cadms . "'> Adm </font>";
                            break;
                        case 6:
                            echo "<font color='" . $cadms . "'> Smd </font>";
                            break;
                        case 2:
                            echo "<font color='" . $cadms . "'> Mod </font>";
                            break;
                        case 1:
                            echo "<font color='" . $cadms . "'> Kil </font>";
                            break;
                    }
                    $ontime = $mass1[lastdate];
                    $ontime2 = $ontime + 300;
                    if ($realtime > $ontime2)
                    {
                        echo "<font color='" . $coffs . "'> [Off]</font>";
                    } else
                    {
                        echo "<font color='" . $cons . "'> [ON]</font>";
                    }
                }
                echo "<font color='" . $cdtim . "'>($vr)</font><br/>";
                if ($pfon == 1)
                {
                    echo "</div>";
                }
                if (!stristr($mass[text], "http://"))
                {
                    $mass[text] = antilink($mass[text]);
                } else
                {
                    $mass[text] = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "***", $mass[text]);
                }
                if ($offsm != 1 && $offgr != 1)
                {
                    $tekst = smiles($mass[text]);
                    $tekst = smilescat($tekst);

                    if ($mass[name] == nickadmina || $mass[name] == nickadmina2 || $mass1[rights] >= 1)
                    {
                        $tekst = smilesadm($tekst);
                    }
                } else
                {
                    $tekst = $mass[text];
                }
                echo "$tekst<br/>";
                if ($dostsmod == 1)
                {
                    echo "<a href='guest.php?act=otvet&amp;id=" . $mass[id] . "'>Отв.</a> | <a href='guest.php?act=edit&amp;id=" . $mass[id] . "'>Изм.</a> | <a href='guest.php?act=delpost&amp;id=" . $mass[id] . "'>Удалить</a><br/>";
                    echo "$mass[ip] - $mass[soft]<br/>";
                }
                if ($mass[otvet] != "")
                {
                    $vrp1 = $mass[otime] + $sdvig * 3600;
                    $vr1 = date("d.m.Y / H:i", $vrp1);
                    $mass[otvet] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div style=\'background:' . $ccfon . ';color:' . $cctx . ';\'>\1<br/></div>', $mass[otvet]);
                    $mass[otvet] = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $mass[otvet]);
                    $mass[otvet] = eregi_replace("\\[l\\]([[:alnum:]_=/:-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/;%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $mass[otvet]);
                    if (stristr($mass[otvet], "<a href="))
                    {
                        $mass[otvet] = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)</a>",
                            "<a href='\\1\\3'>\\3</a>", $mass[otvet]);
                    } else
                    {
                        $mass[otvet] = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $mass[otvet]);
                    }
                    if ($offsm != 1 && $offgr != 1)
                    {
                        $otvet = smiles($mass[otvet]);
                        $otvet = smilescat($otvet);
                        $otvet = smilesadm($otvet);
                    } else
                    {
                        $otvet = $mass[otvet];
                    }
                    echo "<font color='" . $conik . "'>Отвечает <b>$mass[admin]</b>:</font><font color='" . $cdtim . "'>($vr1)</font><br/><font color='" . $cdinf . "'>$otvet</font><br/>";
                }
                echo "</div>";
            }
            ++$i;
        }
        echo "<hr/>Всего сообщений: $colmes<br/>";
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
        }
        break;
}
require ("../incfiles/end.php");
?>