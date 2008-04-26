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

$textl = 'Форум-новые';
$headmod = "forums";
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
    $do = isset($_GET['do']) ? $_GET['do'] : '';
    switch ($do)
    {
        case "razd":
            if (isset($_GET['okey']))
            {
                echo "Сделано!<br/>";
            }
            if (isset($_POST['submit']))
            {
                if (empty($_SESSION['uid']))
                {
                    echo "Только для авторизованных!<br/><a href='index.php?act=new'>Назад</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                $rz = mysql_query("select `id` from `forum` where type='f';");
				while ($rz1 = mysql_fetch_array($rz))
                {
                    $rz2[] = $rz1[id];
                }
                if (isset($_POST['pf']))
                {
                    $q = mysql_query("select `id`, `refid` from `forum` where type='n' and `from`='" . $login . "';");
                    while ($q1 = mysql_fetch_array($q))
                    {
                        if (in_array($q1[refid], $_POST['pf']))
                        {
                            mysql_query("delete from `forum` where `id`='" . $q1[id] . "';");
                        }
                    }
                    foreach ($rz2 as $v)
                    {
                        if (!in_array($v, $_POST['pf']))
                        {
                            $q2 = mysql_query("select `id` from `forum` where type='n' and `from`='" . $login . "' and refid='" . $v . "';");
                            $q3 = mysql_num_rows($q2);
                            if ($q3 == 0)
                            {
                                mysql_query("insert into `forum` values(0,'" . intval(check($v)) . "','n','','" . $login . "','','','','','','','','','','','','');");
                            }
                        }
                    }
                } else
                {
                    $rz = mysql_query("select `id` from `forum` where type='f';");
                    while ($rz3 = mysql_fetch_array($rz))
                    {
                        $q2 = mysql_query("select `id` from `forum` where type='n' and `from`='" . $login . "' and refid='" . $rz3[id] . "';");
                        $q3 = mysql_num_rows($q2);
                        if ($q3 == 0)
                        {
                            mysql_query("insert into `forum` values(0,'" . $rz3[id] . "','n','','" . $login . "','','','','','','','','','','','','');");
                        }
                    }
                }
                header("Location: index.php?act=new&do=razd&okey");
            } else
            {
                if (empty($_SESSION['uid']))
                {
                    echo "Только для авторизованных!<br/><a href='index.php?act=new'>Назад</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                echo "Показывать темы только из подфорумов :<br/><form action='index.php?act=new&amp;do=razd' method='post'>";
                $q = mysql_query("select `id`, `text` from `forum` where type='f';");
                while ($q1 = mysql_fetch_array($q))
                {
                    $q2 = mysql_query("select `id`, `text` from `forum` where type='n' and `from`='" . $login . "' and refid='" . $q1[id] . "';");
                    $q3 = mysql_num_rows($q2);
                    if ($q3 == 0)
                    {
                        echo "<input type='checkbox' name='pf[]' value='" . $q1[id] . "' checked='checked'/>$q1[text]<br/>";
                    } else
                    {
                        echo "<input type='checkbox' name='pf[]' value='" . $q1[id] . "'/>$q1[text]<br/>";
                    }
                }
                echo "<input type='submit' name='submit' value='Ok!'/><br/></form>";
            }
            echo "<br/><a href='index.php?act=new'>Назад</a><br/>";
            break;

        case "reset":
            $lp = mysql_query("select `id`, `time` from `forum` where type='t' and moder='1';");
            while ($arrt = mysql_fetch_array($lp))
            {
                $np = mysql_query("select `id` from `forum` where type='l' and time>'" . $arrt[time] . "' and refid='" . $arrt[id] . "' and `from`='" . $login . "';");
                if ((mysql_num_rows($np)) != 1)
                {
                    $np1 = mysql_query("select `id` from `forum` where type='l' and refid='" . $arrt[id] . "' and `from`='" . $login . "';");
                    if ((mysql_num_rows($np1)) == 0)
                    {
                        mysql_query("insert into `forum` values(0,'" . $arrt[id] . "','l','" . $realtime . "','" . $login . "','','','','','','','','','','','','','');");
                    } else
                    {
                        $np2 = mysql_fetch_array($np1);
                        mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $np2[id] . "';");
                    }
                }
            }
            echo "Все темы приняты как прочитанные<br/>";
            break;

        case "all":
            if (isset($_POST['submit']))
            {
                if (empty($_POST['vr']))
                {
                    echo "Вы не ввели время!<br/><a href='index.php?act=new&amp;do=all'>Повторить</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                $vr = intval(check($_POST['vr']));
                $vr1 = $realtime - $vr * 3600;
                if ($dostsadm == 1)
                {
                    $lpz = mysql_query("select `id` from `forum` where type='t' and moder='1' and time>'" . $vr1 . "' ;");
                } else
                {
                    $lpz = mysql_query("select `id` from `forum` where type='t' and moder='1' and time>'" . $vr1 . "' and close!='1' ;");
                }
                $count = mysql_num_rows($lpz);
                $ba = ceil($count / $kmess);
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
                if ($count < $start + $kmess)
                {
                    $end = $count;
                } else
                {
                    $end = $start + $kmess;
                }
                if ($dostsadm == 1)
                {
                    $lp = mysql_query("select * from `forum` where type='t' and moder='1' and time>'" . $vr1 . "' order by time desc LIMIT " . $start . "," . $end . ";");
                } else
                {
                    $lp = mysql_query("select * from `forum` where type='t' and moder='1' and time>'" . $vr1 . "' and close!='1' order by time desc LIMIT " . $start . "," . $end . ";");
                }
                echo "Все за период $vr часов<br/>";
                $i = 0;
                while ($arr = mysql_fetch_array($lp))
                {
                    $q3 = mysql_query("select `id`, `refid`, `text` from `forum` where type='r' and id='" . $arr[refid] . "';");
                    $razd = mysql_fetch_array($q3);
                    $q4 = mysql_query("select `id`, `refid`, `text` from `forum` where type='f' and id='" . $razd[refid] . "';");
                    $frm = mysql_fetch_array($q4);
                    $colmes = mysql_query("select `id` from `forum` where type='m' and close!='1' and refid='" . $arr[id] . "' order by time desc;");
                    $nikuser = mysql_query("SELECT `from` FROM `forum` WHERE `type` = 'm' AND `close` != '1' AND `refid` = '" . $arr[id] . "'ORDER BY time DESC LIMIT 1;");
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
                    if ($arr[edit] == 1)
                    {
                        echo "<img src='../images/tz.gif' alt=''/>";
                    } else
                    {
                        echo "<img src='../images/np.gif' alt=''/>";
                    }
                    echo "<a href='index.php?id=" . $arr[id] . "'><font color='" . $cntem . "'>$arr[text]</font></a><font color='" . $ccolp . "'>[$colmes1]</font>";
                    if ($cpg > 1)
                    {
                        if (((empty($_SESSION['uid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['uid'])) && $upfp == 1))
                        {
                            echo "<a href='index.php?id=$arr[id]&amp;page=$cpg'>[&lt;&lt;]</a>";
                        } else
                        {
                            echo "<a href='index.php?id=$arr[id]&amp;page=$cpg'>[&gt;&gt;]</a>";
                        }
                    }
                    echo "<br/>";
                    echo "<font color='" . $cdtim . "'>(" . date("H:i /d.m.y", $arr[time]) . ")</font><br/><font color='" . $cssip . "'>[$arr[from]</font>";
                    if (!empty($nam[from]))
                    {
                        echo "<font color='" . $cssip . "'>/$nam[from]</font>";
                    }
                    echo "<font color='" . $cssip . "'>]</font><br/>";
                    echo "$frm[text]/$razd[text]";
                    echo "</div>";

                    $i++;
                }
                echo "<hr/>";
                echo "Всего: $count<br/>";
                if ($count > $kmess)
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
                        echo '<a href="index.php?act=new&amp;page=' . ($page - 1) . '&amp;act=all&amp;vr=' . $vr . '&amp;submit">&lt;&lt;</a> ';
                    }
                    if ($offpg != 1)
                    {
                        navigate('index.php?act=new&amp;do=all&amp;vr=' . $vr . '&amp;submit', $count, $kmess, $start, $page);
                    } else
                    {
                        echo "<b>[$page]</b>";
                    }
                    if ($count > $start + $kmess)
                    {
                        echo ' <a href="index.php?act=new&amp;page=' . ($page + 1) . '&amp;act=all&amp;vr=' . $vr . '&amp;submit">&gt;&gt;</a>';
                    }

                    echo "<form action='index.php?act=new'>Перейти к странице:<br/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='hidden' name='vr' value='" . $vr .
                        "'/><input type='hidden' name='act' value='all'/><input type='submit' name='submit' value='Go!'/></form>";
                }
            } else
            {
                echo "<form action='index.php?act=new&amp;do=all' method='post'>Показать все новые за период(в часах):<br/><input type='text' maxlength='3' name='vr' title='Введите время' value='24'/><input type='hidden' name='act' value='all'/><br/><input type='submit' name='submit' value='Go!'/></form>";
            }
            echo '<a href="index.php?act=new">Вернуться</a><br/>';
            break;

        default:
            if ($dostsadm == 1)
            {
                $lp = mysql_query("select `id`, `time`, `refid` from `forum` where type='t' and moder='1';");
            } else
            {
                $lp = mysql_query("select `id`, `time`, `refid` from `forum` where type='t' and moder='1' and close!='1';");
            }
            $knt = 0;
            while ($arrt = mysql_fetch_array($lp))
            {
                $q3 = mysql_query("select `id`, `refid` from `forum` where type='r' and id='" . $arrt[refid] . "';");
                $q4 = mysql_fetch_array($q3);
                $rz = mysql_query("select `id` from `forum` where type='n' and refid='" . $q4[refid] . "' and `from`='" . $login . "';");
                $np = mysql_query("select `id` from `forum` where type='l' and time>='" . $arrt[time] . "' and refid='" . $arrt[id] . "' and `from`='" . $login . "';");
                if ((mysql_num_rows($np)) != 1 && (mysql_num_rows($rz)) != 1)
                {
                    $knt = $knt + 1;
                }
            }
            $ba = ceil($knt / $kmess);
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
            if ($knt < $start + $kmess)
            {
                $end = $knt;
            } else
            {
                $end = $start + $kmess;
            }
            if ($dostsadm == 1)
            {
                $lp = mysql_query("select * from `forum` where type='t' and moder='1' order by time desc LIMIT " . $start . "," . $end . ";");
            } else
            {
                $lp = mysql_query("select * from `forum` where type='t' and moder='1' and close!='1' order by time desc LIMIT " . $start . "," . $end . ";");
            }
            while ($arrt = mysql_fetch_array($lp))
            {
                $q3 = mysql_query("select `id`, `refid`, `text` from `forum` where type='r' and id='" . $arrt[refid] . "';");
                $q4 = mysql_fetch_array($q3);
                $rz = mysql_query("select `id` from `forum` where type='n' and refid='" . $q4[refid] . "' and `from`='" . $login . "';");
                $np = mysql_query("select `id` from `forum` where type='l' and time>='" . $arrt[time] . "' and refid='" . $arrt[id] . "' and `from`='" . $login . "';");
                if ((mysql_num_rows($np)) != 1 && (mysql_num_rows($rz)) != 1)
                {
                    $q3 = mysql_query("select `id`, `refid`, `text` from `forum` where type='r' and id='" . $arrt[refid] . "';");
                    $razd = mysql_fetch_array($q3);
                    $q4 = mysql_query("select `id`, `refid`, `text` from `forum` where type='f' and id='" . $razd[refid] . "';");
                    $frm = mysql_fetch_array($q4);
                    $colmes = mysql_query("select `id` from `forum` where type='m' and close!='1' and refid='" . $arrt[id] . "' order by time desc;");
                    $nikuser = mysql_query("SELECT `from` FROM `forum` WHERE `type` = 'm' AND `close` != '1' AND `refid` = '" . $arrt[id] . "'ORDER BY time DESC LIMIT 1;");
                    $colmes1 = mysql_num_rows($colmes);
                    $cpg = ceil($colmes1 / $kmess);
                    $colmes1 = $colmes1 - 1;
                    $colmes1 = mysql_num_rows($colmes) - 1;
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
                    if ($arrt[edit] == 1)
                    {
                        echo "<img src='../images/tz.gif' alt=''/>";
                    } else
                    {
                        echo "<img src='../images/np.gif' alt=''/>";
                    }
                    echo "<a href='index.php?id=" . $arrt[id] . "&amp;page=" . $page . "'><font color='" . $cntem . "'>$arrt[text]</font></a><font color='" . $ccolp . "'>[$colmes1]</font>";
                    if ($cpg > 1)
                    {
                        if (((empty($_SESSION['uid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['uid'])) && $upfp == 1))
                        {
                            echo "<a href='index.php?id=$arrt[id]&amp;page=$cpg'>[&lt;&lt;]</a>";
                        } else
                        {
                            echo "<a href='index.php?id=$arrt[id]&amp;page=$cpg'>[&gt;&gt;]</a>";
                        }
                    }
                    echo "<font color='" . $cdtim . "'>(" . date("H:i /d.m.y", $arrt[time]) . ")</font><br/><font color='" . $cssip . "'>[$arrt[from]</font>";
                    if (!empty($nam[from]))
                    {
                        echo "<font color='" . $cssip . "'>/$nam[from]</font>";
                    }
                    echo "<font color='" . $cssip . "'>]</font><br/>";
                    echo "$frm[text]/$razd[text]";
                    echo "</div>";
                    $i++;
                }
            }
            echo "<hr/>";
            echo "Всего: $knt<br/>";
            if ($knt > $kmess)
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
                    echo '<a href="index.php?act=new&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                }
                if ($offpg != 1)
                {
                    navigate('index.php?act=new', $knt, $kmess, $start, $page);
                } else
                {
                    echo "<b>[$page]</b>";
                }
                if ($knt > $start + $kmess)
                {
                    echo ' <a href="index.php?act=new&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                }
                echo "<form action='index.php?act=new'>Перейти к странице:<br/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
            }
            echo '<a href="index.php?act=new&amp;do=reset">Сброс!</a><br/>';
            echo '<a href="index.php?act=new&amp;do=all">Показать за период...</a><br/>';
            echo '<a href="index.php?act=new&amp;do=razd">Выбор подфорумов</a><br/>';
            if (empty($_SESSION['uid']))
            {
                if ((empty($_SESSION['uppost'])) || ($_SESSION['uppost'] == 0))
                {
                    echo "&#187;<a href='index.php?act=new&amp;page=" . $page . "&amp;newup'>Новые вверху</a><br/>";
                } else
                {
                    echo "&#187;<a href='index.php?act=new&amp;page=" . $page . "&amp;newdown'>Новые внизу</a><br/>";
                }
            }
            break;
    }
} else
{
    $lp = mysql_query("select * from `forum` where type='t' and moder='1' order by time desc LIMIT 10;");
    while ($arr = mysql_fetch_array($lp))
    {
        $q3 = mysql_query("select `id`, `refid`, `text` from `forum` where type='r' and id='" . $arr[refid] . "';");
        $razd = mysql_fetch_array($q3);
        $q4 = mysql_query("select `id`, `refid`, `text` from `forum` where type='f' and id='" . $razd[refid] . "';");
        $frm = mysql_fetch_array($q4);
        $colmes = mysql_query("select `id` from `forum` where type='m' and close!='1' and refid='" . $arr[id] . "' order by time desc;");
        $nikuser = mysql_query("SELECT `from` FROM `forum` WHERE `type` = 'm' AND `close` != '1' AND `refid` = '" . $arr[id] . "'ORDER BY time DESC LIMIT 1;");
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
        if ($arrt[edit] == 1)
        {
            echo "<img src='../images/tz.gif' alt=''/>";
        } else
        {
            echo "<img src='../images/np.gif' alt=''/>";
        }
        echo "<a href='index.php?id=" . $arr[id] . "'>$arr[text]</a>[$colmes1]";
        if ($cpg > 1)
        {
            if (((empty($_SESSION['uid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['uid'])) && $upfp == 1))
            {
                echo "<a href='index.php?id=$arr[id]&amp;page=$cpg'>[&lt;&lt;]</a>";
            } else
            {
                echo "<a href='index.php?id=$arr[id]&amp;page=$cpg'>[&gt;&gt;]</a>";
            }
        }
        echo "<br/>";


        echo "(" . date("H:i /d.m.y", $arr[time]) . ")<br/>[$arr[from]";
        if (!empty($nam[from]))
        {
            echo "/$nam[from]";
        }
        echo "]<br/>";
        echo "$frm[text]/$razd[text]";
        echo "</div>";
        $i++;

    }
}
echo '<a href="index.php">В форум</a>';
require_once ("../incfiles/end.php");
?>