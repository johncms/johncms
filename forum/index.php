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

$textl = 'Форум';
$headmod = "forum";
require_once ("../incfiles/core.php");

if (!empty($_SESSION['uid']))
{
    $tti = round(($datauser['ftime'] - $realtime) / 60);
    if (!empty($_GET['id']))
    {
        $id = intval(check($_GET['id']));
        $where = "forum,$id";
    } else
    {
        $where = "forum";
    }
    mysql_query("insert into `count` values(0,'" . $ipp . "','" . $agn . "','" . $realtime . "','" . $where . "','" . $login . "','0');");
}
if (!empty($_GET['act']))
{
    $act = check($_GET['act']);
}
switch ($act)
{
    case 'new':
        require_once ('new.php');
        break;

    case 'who':
        require_once ('who.php');
        break;

    case 'addfile':
        require_once ('addfile.php');
        break;

    case 'file':
        require_once ('file.php');
        break;

    case 'moders':
        require_once ('moders.php');
        break;

    case 'per':
        require_once ('per.php');
        break;

    case 'fmoder':
        require_once ('fmoder.php');
        break;

    case 'ren':
        require_once ('ren.php');
        break;

    case 'deltema':
        require_once ('deltema.php');
        break;

    case 'vip':
        require_once ('vip.php');
        break;

    case 'close':
        require_once ('close.php');
        break;

    case 'delpost':
        require_once ('delpost.php');
        break;

    case 'read':
        require_once ("../incfiles/head.php");
        include ("../pages/forum.txt");
        echo "<a href='index.php'>В форум</a><br/>";
        break;

    case 'faq':
        require_once ("../incfiles/head.php");
        include ("../pages/forumfaq.txt");
        echo "<a href='index.php'>В форум</a><br/>";
        break;

    case 'editpost':
        require_once ('editpost.php');
        break;

    case 'nt':
        require_once ('nt.php');
        break;

    case 'tema':
        require_once ('tema.php');
        break;

    case 'loadtem':
        require_once ('loadtem.php');
        break;

    case 'trans':
        require_once ("../incfiles/head.php");
        include ("../pages/trans.$ras_pages");
        echo '<br/><br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
        break;

    case 'say':
        require_once ('say.php');
        break;

    case 'post':
        require_once ('post.php');
        break;

    default:
        require_once ("../incfiles/head.php");
        if (empty($_SESSION['uid']))
        {
            if (isset($_GET['newup']))
            {
                $_SESSION['uppost'] = 1;
            }
            if (isset($_GET['newdown']))
            {
                $_SESSION['uppost'] = 0;
            }
        }
        if (!empty($_SESSION['uid']))
        {
            $lp = mysql_query("select `id`, `refid`, `time` from `forum` where type='t' and moder='1' and close!='1';");
            $knt = 0;
            while ($arrt = mysql_fetch_array($lp))
            {
                $q3 = mysql_query("select `refid` from `forum` where type='r' and id='" . $arrt[refid] . "';");
                $q4 = mysql_fetch_array($q3);
                $rz = mysql_query("select `id` from `forum` where type='n' and refid='" . $q4[refid] . "' and `from`='" . $login . "';");
                $np = mysql_query("select `id` from `forum` where type='l' and time>='" . $arrt[time] . "' and refid='" . $arrt[id] . "' and `from`='" . $login . "';");
                if ((mysql_num_rows($np)) != 1 && (mysql_num_rows($rz)) != 1)
                {
                    $knt = $knt + 1;
                }
            }
            echo "<p><a href='index.php?act=new'>Новые: $knt</a></p>";
        } else
        {
            echo "<p><a href='index.php?act=new'>10 новых</a></p>";
        }
        if ($dostfmod == 1)
        {
            $fm = mysql_query("select `id` from `forum` where type='t' and moder!='1';");
            $fm1 = mysql_num_rows($fm);
            if ($fm1 != 0)
            {
                echo "Модерацию ожидают <a href='index.php?act=fmoder'>$fm1</a> тем<br/>";
            }
        }
        if (empty($_GET['id']))
        {
            // Список главных разделов
			echo "<b>Все форумы</b><hr/>";
            $q = mysql_query("select `id`, `text` from `forum` where type='f' order by realid ;");
            while ($mass = mysql_fetch_array($q))
            {
                $colraz = mysql_query("select `id` from `forum` where type='r' and refid='" . $mass[id] . "';");
                $colraz1 = mysql_num_rows($colraz);
                echo "<div class=\"menu\"><img alt=\"\" src=\"../images/arrow.gif\" width=\"7\" height=\"12\" />&nbsp;<a href='index.php?id=" . $mass[id] . "'>$mass[text]</a> [$colraz1]</div>";
            }
            echo "<hr/><p>";
            if (!empty($_SESSION['uid']))
            {
                echo '<a href="index.php?act=who">Кто в форуме(' . wfrm() . ')</a><br/>';
            }
            echo "<a href='search.php'>Поиск по форуму</a><br/>";
            echo "<a href='../str/usset.php?act=forum'>Настройки форума</a><br/>";
            echo "<a href='index.php?act=read'>Правила форума</a><br/>";
            echo "<a href='index.php?act=faq'>FAQ</a><br/>";
        }
        if (!empty($_GET['id']))
        {
            $id = intval($_GET['id']);
            $type = mysql_query("select * from `forum` where id= '" . $id . "';");
            $type1 = mysql_fetch_array($type);
            $tip = $type1['type'];
            switch ($tip)
            {
                case "f":
                    // Список подразделов
					echo "<b>$type1[text]</b><hr/>";
                    $q1 = mysql_query("select id, text from `forum` where type='r' and refid='" . $id . "'  order by realid ;");
                    $colraz2 = mysql_num_rows($q1);
                    $i = 0;
                    while ($mass1 = mysql_fetch_array($q1))
                    {
                        $coltem = mysql_query("select id, time from `forum` where type='t' and moder='1' and refid='" . $mass1[id] . "' order by time desc;");
                        $coltem1 = mysql_num_rows($coltem);
                        $cmes = mysql_query("select time from `forum` where type='t' and moder='1' and refid='" . $mass1[id] . "' order by time desc LIMIT 1;");
                        $clm = 0;
                        while ($arr1 = mysql_fetch_array($coltem))
                        {
                            $colmes = mysql_query("select id from `forum` where type='m' and refid='" . $arr1[id] . "' ;");
                            $colmes1 = mysql_num_rows($colmes);
                            $clm = $clm + $colmes1;
                        }
                        $cmes = mysql_query("select time from `forum` where type='t' and moder='1' and refid='" . $mass1[id] . "' order by time desc LIMIT 1;");
                        $arr = mysql_fetch_array($cmes);
                        $posl = $arr[time];
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
                        echo "$div<a href='?id=$mass1[id]'>$mass1[text]</a>";
                        if ($coltem1 > 0)
                        {
                            echo " [$coltem1/$clm]<br/>(" . date("H:i /d.m.y", $posl) . ")";
                        }
                        echo "</div>";
                        ++$i;
                    }
                    echo "<hr/><p><a href='?'>В форум</a><br/>";
                    break;

                case "r":
                    // Список тем
					if ($dostsadm == 1)
                    {
                        $qz = mysql_query("select `id` from `forum` where type='t' and refid='" . $id . "' and moder='1' ;");
                    } else
                    {
                        $qz = mysql_query("select `id` from `forum` where type='t' and close!='1' and moder='1' and refid='" . $id . "'  ;");
                    }
                    $coltem = mysql_num_rows($qz);
                    $ba = ceil($coltem / $kmess);
                    if (empty($_GET['page']))
                    {
                        $page = 1;
                    } else
                    {
                        $page = intval($_GET['page']);
                    }
                    if ($page < 1)
                    {
                        $page = 1;
                    }
                    if ($page > $ba)
                    {
                        $page = $ba;
                    }
                    $start = $page * $kmess - $kmess;
                    if ($dostsadm == 1)
                    {
                        $q1 = mysql_query("SELECT `id`, `from`, `time`, `vip`, `close`, `edit`, `text` FROM `forum` WHERE `type`='t' AND `refid`='" . $id . "' AND `moder`='1'  ORDER BY vip DESC, time DESC LIMIT " . $start . "," . $kmess . ";");
                    } else
                    {
                        $q1 = mysql_query("SELECT `id`, `from`, `time`, `vip`, `close`, `edit`, `text` FROM `forum` WHERE `type`='t' AND `close`!='1' AND `moder`='1' AND `refid`='" . $id . "'  ORDER BY vip DESC, time DESC LIMIT " . $start . "," . $kmess . ";");
                    }
                    echo "<b><font color='" . $cntem . "'>$type1[text]</font></b><br/><font color='" . $ccolp . "'>Тем в разделе: $coltem</font><br/>";
                    if (!empty($_SESSION['uid']))
                    {
                        echo "<a href='index.php?act=nt&amp;id=" . $id . "'>Новая тема</a>";
                    }
                    echo "<hr/>";
                    $i = 0;
                    while ($mass = mysql_fetch_array($q1))
                    {
                        $colmes = mysql_query("SELECT `id` FROM `forum` WHERE `type` = 'm' AND `close` != '1' AND `refid` = '" . $mass[id] . "'ORDER BY time DESC;");
                        $nikuser = mysql_query("SELECT `from` FROM `forum` WHERE `type` = 'm' AND `close` != '1' AND `refid` = '" . $mass[id] . "'ORDER BY time DESC LIMIT 1;");
                        $colmes1 = mysql_num_rows($colmes);
                        $cpg = ceil($colmes1 / $kmess);
                        $colmes1 = $colmes1 - 1;
                        if ($colmes1 < 0)
                        {
                            $colmes1 = 0;
                        }
                        $nam = mysql_fetch_array($nikuser);
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
                        echo "$div";
                        if ($mass[vip] == 1)
                        {
                            echo "<img src='../images/pt.gif' alt=''/>";
                        } elseif ($mass[edit] == 1)
                        {
                            echo "<img src='../images/tz.gif' alt=''/>";
                        } else
                        {
                            $np = mysql_query("SELECT `id` FROM `forum` WHERE `type`='l' AND `time`>='" . $mass[time] . "' AND `refid`='" . $mass[id] . "' and `from`='" . $login . "';");
                            $np1 = mysql_num_rows($np);
                            if ($np1 == 0)
                            {
                                echo "<img src='../images/np.gif' alt=''/>";
                            } else
                            {
                                echo "<img src='../images/op.gif' alt=''/>";
                            }
                        }
                        // Выводим список тем
                        echo "<a href='index.php?id=$mass[id]'><font color='" . $cntem . "'>$mass[text]</font></a><font color='" . $ccolp . "'> [$colmes1]</font>";
                        if ($cpg > 1)
                        {
                            if (((empty($_SESSION['uid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['uid'])) && $upfp == 1))
                            {
                                echo "<a href='index.php?id=$mass[id]&amp;page=$cpg'>[&lt;&lt;]</a>";
                            } else
                            {
                                echo "<a href='index.php?id=$mass[id]&amp;page=$cpg'>[&gt;&gt;]</a>";
                            }
                        }
                        echo "<br/>";
                        if ($mass[close] == 1)
                        {
                            echo "<font color='" . $cdinf . "'>Тема удалена!</font><br/>";
                        }
                        echo "<font color='" . $cdtim . "'>(" . date("H:i /d.m.y", $mass[time]) . ")</font><br/><font color='" . $cssip . "'>[$mass[from]</font>";
                        if (!empty($nam[from]))
                        {
                            echo "<font color='" . $cssip . "'>/$nam[from]</font>";
                        }
                        echo "<font color='" . $cssip . "'>]</font></div>";
                        ++$i;
                    }
                    echo "<hr/><p>";
                    if ($coltem > $kmess)
                    {
                        if ($offpg != 1)
                        {
                            echo "Страницы:<br/>";
                        } else
                        {
                            echo "Страниц: $ba<br/>";
                        }
                        if ($start != 0)
                        {
                            echo '<a href="index.php?id=' . $id . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                        }
                        if ($offpg != 1)
                        {
                            navigate('index.php?id=' . $id . '', $coltem, $kmess, $start, $page); #функция постраничного вывода из файла incfiles/end.php
                        } else
                        {
                            echo "<b>[$page]</b>";
                        }
                        if ($coltem > $start + $kmess)
                        {
                            echo ' <a href="index.php?id=' . $id . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                        }
                        echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
                            "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
                    }
                    $forum = mysql_query("select * from `forum` where type='f' and id='" . $type1[refid] . "';");
                    $forum1 = mysql_fetch_array($forum);
                    echo "&#187;<a href='?id=" . $type1[refid] . "'>$forum1[text]</a><br/>";
                    echo "&#187;<a href='?'>В форум</a><br/>";
                    break;

                case "t":
                    // Читаем тему
                    if (!empty($_SESSION['uid']))
                    {
                        //блок, фиксирующий факт прочтения темы
						$np = mysql_query("select `id` from `forum` where type='l' and refid='" . $id . "' and `from`='" . $login . "';");
                        $np1 = mysql_num_rows($np);
                        if ($np1 == 0)
                        {
                            mysql_query("insert into `forum` values(0,'" . $id . "','l','" . $realtime . "','" . $login . "','','','','','','','','','','','','','');");
                        } else
                        {
                            $np2 = mysql_fetch_array($np);
                            mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $np2[id] . "';");
                        }
                    }
                    if ($dostsadm != 1 && $type1[close] == 1)
                    {

                        echo "<font color='" . $cdinf . "'>Тема удалена!</font><br/><a href='?id=" . $type1[refid] . "'>В раздел</a><br/>";
                        require_once ("../incfiles/end.php");
                        exit;
                    }
                    $id = intval(check($_GET['id']));
                    if ($dostsadm == 1)
                    {
                        $qz = mysql_query("select `id` from `forum` where type='m' and refid='" . $id . "' ;");
                    } else
                    {
                        $qz = mysql_query("select `id` from `forum` where type='m' and close!='1' and refid='" . $id . "'  ;");
                    }
                    $colmes = mysql_num_rows($qz);
                    $ba = ceil($colmes / $kmess);
                    if (empty($_GET['page']))
                    {
                        $page = 1;
                    } else
                    {
                        $page = intval($_GET['page']);
                    }
                    if ($page < 1)
                    {
                        $page = 1;
                    }
                    if ($page > $ba)
                    {
                        $page = $ba;
                    }
                    $start = $page * $kmess - $kmess;
                    if (((empty($_SESSION['uid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['uid'])) && $upfp == 1))
                    {
                        if ($dostsadm == 1)
                        {
                            $q1 = mysql_query("SELECT * FROM `forum` WHERE type='m' AND refid='" . $id . "'  ORDER BY time DESC LIMIT " . $start . ", " . $kmess . " ;");
                        } else
                        {
                            $q1 = mysql_query("SELECT * FROM `forum` WHERE type='m' AND close!='1' AND refid='" . $id . "'  ORDER BY time DESC LIMIT " . $start . ", " . $kmess . "  ;");
                        }
                    } else
                    {
                        if ($dostsadm == 1)
                        {
                            $q1 = mysql_query("SELECT * FROM `forum` WHERE type='m' AND refid='" . $id . "'  ORDER BY time LIMIT " . $start . ", " . $kmess . " ;");
                        } else
                        {
                            $q1 = mysql_query("SELECT * FROM `forum` WHERE type='m' AND close!='1' AND refid='" . $id . "'  ORDER BY time LIMIT " . $start . ", " . $kmess . " ;");
                        }
                    }
                    // Выводим название темы
                    echo "<b>$type1[text]</b><br/>Сообщений: $colmes<br/>";
                    if ($type1[edit] == 1)
                    {
                        echo "<b>Тема закрыта</b><br/>";
                    }
                    if ($type1['edit'] != 1 && $_SESSION['uid'] != "" && $upfp == 1)
                    {
                        if ($datauser['farea'] == 1 && $datauser['postforum'] >= 1)
                        {
                            echo "<div class='e'>Написать<br/><form action='index.php?act=say&amp;id=" . $id . "' method='post' enctype='multipart/form-data'><textarea cols='20' rows='2' title='Введите текст сообщения' name='msg'></textarea><br/>";
                            echo "<input type='checkbox' name='addfiles' value='1' /> Добавить файл<br/>";
                            if ($offtr != 1)
                            {
                                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
                            }
                            echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form></div>";
                        } else
                        {
                            echo "<a href='?act=say&amp;id=" . $id . "'>Написать</a>";
                        }
                    }
                    echo '<a name="up" id="up"></a><a href="#down">Вниз</a><hr />';
                    while ($mass = mysql_fetch_array($q1))
                    {
                        if ($i >= 0 && $i < $colmes)
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
                            $uz = mysql_query("SELECT `id`, 'from', `sex`, `rights`, `lastdate`, `dayb` FROM `users` WHERE `name`='" . $mass[from] . "';");
                            $mass1 = mysql_fetch_array($uz);
                            echo "$div";
                            switch ($mass1[sex])
                            {
                                case "m":
                                    echo "<img src='../images/m.gif' alt=''/>";
                                    break;
                                case "zh":
                                    echo "<img src='../images/f.gif' alt=''/>";
                                    break;
                            }
                            if ((!empty($_SESSION['uid'])) && ($_SESSION['uid'] != $mass1[id]))
                            {
                                echo "<a href='index.php?act=say&amp;id=" . $mass[id] . "'><b><font color='" . $conik . "'>$mass[from]</font></b></a> <a href='index.php?act=say&amp;id=" . $mass[id] . "&amp;cyt'><font color='" . $conik . "'> [ц]</font></a>";
                            } else
                            {
                                echo "<b><font color='" . $csnik . "'>$mass[from]</font></b>";
                            }
                            $vrp = $mass[time] + $sdvig * 3600;
                            $vr = date("d.m.Y / H:i", $vrp);
                            switch ($mass1[rights])
                            {
                                case 7:
                                    echo "<font color='" . $cadms . "'> Adm </font>";
                                    break;
                                case 6:
                                    echo "<font color='" . $cadms . "'> Smd </font>";
                                    break;
                                case 3:
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
                                echo '<font color="#FF0000"> [Off]</font>';
                            } else
                            {
                                echo '<font color="#00AA00"> [ON]</font>';
                            }
                            if ($mass1[dayb] == $day && $mass1[monthb] == $mon)
                            {
                                echo "<font color='" . $cdinf . "'>!!!</font><br/>";
                            }
                            echo "<font color='" . $cdtim . "'>($vr)</font><br/>";
                            if ($mass[close] == 1)
                            {
                                echo "<font color='" . $cdinf . "'>Пост удалён!</font><br/>";
                            }
                            if (!empty($mass[to]))
                            {
                                echo "$mass[to], ";
                            }
                            $lpost1 = mb_strlen($mass['text']);
                            $mass['text'] = tegi($mass['text']);
                            if ($offsm != 1 && $offgr != 1)
                            {
                                $tekst = smiles($mass['text']);
                                $tekst = smilescat($tekst);

                                if ($mass['from'] == nickadmina || $mass['from'] == nickadmina2 || $mass1['rights'] >= 1)
                                {
                                    $tekst = smilesadm($tekst);
                                }
                            } else
                            {
                                $tekst = $mass[text];
                            }
                            if ($lpost1 >= 500)
                            {
                                $tekst = str_replace("</div>", "<hr/>", $tekst);
                                $tekst = strip_tags($tekst, "<br/>,<hr/>");
                                $tekst = mb_strcut($tekst, 0, 500);
                                $tekst = "$tekst...";
                            }
                            echo "$tekst";
                            if ($mass[kedit] >= 1)
                            {
                                $diz = $mass[tedit] + $sdvig * 3600;
                                $dizm = date("d.m /H:i", $diz);
                                echo "<br /><font color='#999999'>Посл. изм. <b>$mass[edit]</b>  ($dizm)<br />Всего изм.:<b> $mass[kedit]</b></font>";
                            }
                            if ($lpost1 >= 500)
                            {
                                echo "<br /><a href='index.php?act=post&amp;s=" . $page . "&amp;id=" . $mass[id] . "'>Весь пост</a>";
                            }
                            if (!empty($mass['attach']))
                            {
                                $fls = filesize("./files/$mass[attach]");
                                $fls = round($fls / 1024, 2);
                                echo "<br /><font color='#999999'>Прикреплённый файл:<br /><a href='index.php?act=file&amp;id=" . $mass['id'] . "'>$mass[attach]</a> ($fls кб.)<br/>";
                                echo 'Скачано: '.$mass['dlcount'].' раз.</font>';
                            }
                            $lp = mysql_query("select `from`, `id` from `forum` where type='m' and refid='" . $id . "'  order by time desc LIMIT 1;");
                            $arr1 = mysql_fetch_array($lp);
                            $tpp = $realtime - 300;
                            if (($dostfmod == 1) || (($arr1[from] == $login) && ($arr1[id] == $mass[id]) && ($mass[time] > $tpp)))
                            {
                                echo "<br /><a href='index.php?act=editpost&amp;id=" . $mass[id] . "'>Изменить</a>";
                            }
                            if ($dostfmod == 1)
                            {
                                echo "|<a href='?act=delpost&amp;id=" . $mass[id] . "'>Удалить</a><br/>";
                                echo "$mass[ip] - $mass[soft]<br/>";
                            }
                            echo '</div>';
                        }
                        ++$i;
                    }
                    echo "<hr /><div id='down'><a href='#up'>Вверх</a></div>";
                    if ($type1['edit'] != 1 && $_SESSION['uid'] != "" && $upfp != 1)
                    {
                        if ($datauser['farea'] == 1 && $datauser['postforum'] >= 1)
                        {
                            echo "<div class='e'>Написать<br/><form action='index.php?act=say&amp;id=" . $id . "' method='post' enctype='multipart/form-data'><textarea cols='20' rows='2' title='Введите текст сообщения' name='msg'></textarea><br/>";
                            echo "<input type='checkbox' name='addfiles' value='1' /> Добавить файл<br/>";
                            if ($offtr != 1)
                            {
                                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
                            }
                            echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form></div>";
                        } else
                        {
                            echo "<a href='?act=say&amp;id=" . $id . "'>Написать</a>";
                        }
                    }
                    if ($colmes > $kmess)
                    {
                        echo "</div><div class='a'><div class='e'>";
                        $ba = ceil($colmes / $kmess);
                        if ($offpg != 1)
                        {
                            echo "Страницы:<br/>";
                        } else
                        {
                            echo "Страниц: $ba<br/>";
                        }
                        if ($start != 0)
                        {
                            echo '<a href="?id=' . $id . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                        }
                        if ($offpg != 1)
                        {
                            navigate('index.php?id=' . $id . '', $colmes, $kmess, $start, $page);
                        } else
                        {
                            echo "<b>[$page]</b>";
                        }
                        if ($colmes > $start + $kmess)
                        {
                            echo ' <a href="?id=' . $id . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                        }
                        echo "<form action='?'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
                            "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form></div>";
                    }
                    echo "<p>";
                    if ($dostfmod == 1)
                    {
                        if ($type1['moder'] != 1)
                        {
                            echo "<a href='index.php?act=fmoder&amp;id=" . $id . "'>Принять тему</a><br/>";
                        }
                        echo "<a href='index.php?act=ren&amp;id=" . $id . "'>Переименовать тему</a><br/>";
                        if ($type1['edit'] == 1)
                        {
                            echo "<a href='index.php?act=close&amp;id=" . $id . "'>Открыть тему</a><br/>";
                        } else
                        {
                            echo "<a href='index.php?act=close&amp;id=" . $id . "&amp;closed'>Закрыть тему</a><br/>";
                        }
                        echo "<a href='index.php?act=deltema&amp;id=" . $id . "'>Удалить тему</a><br/>";
                        if ($type1['vip'] == 1)
                        {
                            echo "<a href='index.php?act=vip&amp;id=" . $id . "'>Открепить тему</a>";
                        } else
                        {
                            echo "<a href='index.php?act=vip&amp;id=" . $id . "&amp;vip'>Закрепить тему</a>";
                        }
                        echo "<br/><a href='index.php?act=per&amp;id=" . $id . "'>Переместить тему</a><br /><br />";
                    }
                    $q3 = mysql_query("select `id`, `refid`, `text` from `forum` where type='r' and id='" . $type1['refid'] . "';");
                    $razd = mysql_fetch_array($q3);
                    $q4 = mysql_query("select `id`, `text` from `forum` where type='f' and id='" . $razd['refid'] . "';");
                    $frm = mysql_fetch_array($q4);
                    echo "&#187;<a href='index.php?id=" . $type1['refid'] . "'>$razd[text]</a><br/>";
                    echo "&#187;<a href='index.php?id=" . $razd['refid'] . "'>$frm[text]</a><br/>";
                    echo "&#187;<a href='index.php?'>В форум</a><br /><br />";
                    if (!empty($_SESSION['uid']))
                    {
                        echo "<a href='index.php?act=who&amp;id=" . $id . "'>Кто здесь?(" . wfrm($id) . ")</a><br/>";
                    }
                    echo "<a href='index.php?act=tema&amp;id=" . $id . "'>Скачать тему</a><br/>";
                    break;

                default:
                    echo "Ошибка:тема удалена или не существует!<br/>&#187;<a href='?'>В форум</a><br/>";
                    break;
            }
        }
        if (empty($_SESSION['uid']))
        {
            if ((empty($_SESSION['uppost'])) || ($_SESSION['uppost'] == 0))
            {
                echo "<a href='index.php?id=" . $id . "&amp;page=" . $page . "&amp;newup'>Новые вверху</a><br/>";
            } else
            {
                echo "<a href='index.php?id=" . $id . "&amp;page=" . $page . "&amp;newdown'>Новые внизу</a><br/>";
            }
        }
        echo "<a href='index.php?act=moders&amp;id=" . $id . "'>Модераторы</a></p>";
        break;
}

require_once ("../incfiles/end.php");

?>