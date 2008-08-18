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
session_name("SESID");
session_start();
$headmod = 'online';
$textl = 'Онлайн';
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");
echo '<div class="phdr">Кто в онлайне?</div>';
$onltime = $realtime - 300;
$q = @mysql_query("select * from `users` where lastdate>='" . intval($onltime) . "';");
$count = mysql_num_rows($q);
if (empty($_GET['page']))
{
    $page = 1;
} else
{
    $page = intval($_GET['page']);
}
$start = $page * 10 - 10;
if ($count < $start + 10)
{
    $end = $count;
} else
{
    $end = $start + 10;
}
while ($arr = mysql_fetch_array($q))
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
        if ($arr['sex'] == "m")
        {
            $pol = "<img src='../images/m.gif' alt=''/>";
        } elseif ($arr['sex'] == "zh")
        {
            $pol = "<img src='../images/f.gif' alt=''/>";
        }

        if (empty($_SESSION['uid']) || $_SESSION['uid'] == $arr['id'])
        {
            print "$div $pol <b>$arr[name]</b>";
        } else
        {
            print "$div $pol <a href='anketa.php?user=" . $arr['id'] . "'>$arr[name]</a>";
        }
        switch ($arr['rights'])
        {
            case 7:
                echo ' Adm ';
                break;
            case 6:
                echo ' Smd ';
                break;
            case 5:
                echo ' Mod ';
                break;
            case 4:
                echo ' Mod ';
                break;
            case 3:
                echo ' Mod ';
                break;
            case 2:
                echo ' Mod ';
                break;
            case 1:
                echo ' Kil ';
                break;
        }
        $prh = @mysql_query("select * from `count` where time>='" . intval($arr['sestime']) . "' and name='" . $arr['name'] . "';");
        $prh1 = mysql_num_rows($prh);
        $svr = $realtime - $arr['sestime'];
        if ($svr >= "3600")
        {
            $hvr = ceil($svr / 3600) - 1;
            if ($hvr < 10)
            {
                $hvr = "0$hvr";
            }
            $svr1 = $svr - $hvr * 3600;
            $mvr = ceil($svr1 / 60) - 1;
            if ($mvr < 10)
            {
                $mvr = "0$mvr";
            }
            $ivr = $svr1 - $mvr * 60;
            if ($ivr < 10)
            {
                $ivr = "0$ivr";
            }
            if ($ivr == "60")
            {
                $ivr = "59";
            }
            $sitevr = "$hvr:$mvr:$ivr";
        } else
        {
            if ($svr >= "60")
            {
                $mvr = ceil($svr / 60) - 1;
                if ($mvr < 10)
                {
                    $mvr = "0$mvr";
                }
                $ivr = $svr - $mvr * 60;
                if ($ivr < 10)
                {
                    $ivr = "0$ivr";
                }
                if ($ivr == "60")
                {
                    $ivr = "59";
                }
                $sitevr = "00:$mvr:$ivr";
            } else
            {
                $ivr = $svr;
                if ($ivr < 10)
                {
                    $ivr = "0$ivr";
                }
                $sitevr = "00:00:$ivr";
            }
        }
        echo '(' . $prh1 . ' - ' . $sitevr . ')<br/>';
        if ($dostmod == 1)
        {
            echo long2ip($arr['ip']) . "--$arr[browser]<br/>";
        }
        if (!empty($_SESSION['uid']))
        {
            echo "Где: ";
            $wh = mysql_query("select * from `count` where name='" . $arr['name'] . "' order by time desc ;");
            $i1 = 0;
            while ($wh1 = mysql_fetch_array($wh))
            {
                if ($i1 < 1)
                {
                    $wher = $wh1['where'];
                    $wher1 = explode(",", $wher);
                    $where = $wher1[0];
                }
                ++$i1;
            }
            switch ($where)
            {
                case "mainpage":
                    echo "<a href='../index.php'>на главной</a>";
                    break;
                case "anketa":
                    echo "<a href='anketa.php'>в анкете</a>";
                    break;
                case "settings":
                    echo "<a href='usset.php'>в настройках</a>";
                    break;
                case "users":
                    echo "<a href='users.php'>в списке юзеров</a>";
                    break;
                case "online":
                    echo "тут";
                    break;
                case "privat":
                    echo "<a href='privat.php'>в привате</a>";
                    break;
                case "pradd":
                    echo "<a href='privat.php'>в привате</a>";
                    break;
                case "birth":
                    echo "<a href='brd.php'>в списке именинников</a>";
                    break;
                case "read":
                    echo "<a href='../read.php'>читает FAQ</a>";
                    break;
                case "load":
                    echo "<a href='../download/download.php'>в загрузках</a>";
                    break;
                case "upload":
                    echo "<a href='../download/upload.php'>в обменнике</a>";
                    break;
                case "gallery":
                    echo "<a href='../gallery/index.php'>в галерее</a>";
                    break;
                case "forum":
                    echo "<a href='../forum/index.php'>в форуме</a>";
                    break;
                case "forums":
                    echo "<a href='../forum/index.php'>в форуме</a>";
                    break;
                case "chat":
                    echo "<a href='../chat/index.php'>в чате</a>";
                    break;
                case "znak":
                    echo "<a href='znak.php'>в знакомствах</a>";
                    break;
                case "guest":
                    echo "<a href='guest.php'>в гостевой</a>";
                    break;
                case "lib":
                    echo "<a href='../library/index.php'>в библиотеке</a>";
                    break;
                default:
                    echo "<a href='../index.php'>на главной</a>";
                    break;
            }
            echo "<br/>";
        }
        echo "</div>";
    }
    ++$i;
}
if ($count > 10)
{
    echo "<hr/>";
    $ba = ceil($count / 10);
    if ($offpg != 1)
    {
        echo "Страницы:<br/>";
    } else
    {
        echo "Страниц: $ba<br/>";
    }
    $asd = $start - (10);
    $asd2 = $start + (10 * 2);

    if ($start != 0)
    {
        echo '<a href="online.php?page=' . ($page - 1) . '">&lt;&lt;</a> ';
    }
    if ($offpg != 1)
    {
        if ($asd < $count && $asd > 0)
        {
            echo ' <a href="online.php?page=1&amp;">1</a> .. ';
        }
        $page2 = $ba - $page;
        $pa = ceil($page / 2);
        $paa = ceil($page / 3);
        $pa2 = $page + floor($page2 / 2);
        $paa2 = $page + floor($page2 / 3);
        $paa3 = $page + (floor($page2 / 3) * 2);
        if ($page > 13)
        {
            echo ' <a href="ponline.php?page=' . $paa . '">' . $paa . '</a> <a href="online.php?page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="online.php?page=' . ($paa * 2) . '">' . ($paa * 2) . '</a> <a href="online.php?page=' . ($paa *
                2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
        } elseif ($page > 7)
        {
            echo ' <a href="online.php?page=' . $pa . '">' . $pa . '</a> <a href="online.php?page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
        }
        for ($i = $asd; $i < $asd2; )
        {
            if ($i < $count && $i >= 0)
            {
                $ii = floor(1 + $i / 10);

                if ($start == $i)
                {
                    echo " <b>$ii</b>";
                } else
                {
                    echo ' <a href="online.php?page=' . $ii . '">' . $ii . '</a> ';
                }
            }
            $i = $i + 10;
        }
        if ($page2 > 12)
        {
            echo ' .. <a href="online.php?page=' . $paa2 . '">' . $paa2 . '</a> <a href="online.php?page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="online.php?page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="online.php?page=' . ($paa3 +
                1) . '">' . ($paa3 + 1) . '</a> ';
        } elseif ($page2 > 6)
        {
            echo ' .. <a href="online.php?page=' . $pa2 . '">' . $pa2 . '</a> <a href="online.php?page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
        }
        if ($asd2 < $count)
        {
            echo ' .. <a href="online.php?page=' . $ba . '">' . $ba . '</a>';
        }
    } else
    {
        echo "<b>[$page]</b>";
    }
    if ($count > $start + 10)
    {
        echo ' <a href="online.php?page=' . ($page + 1) . '">&gt;&gt;</a>';
    }
    echo "<form action='online.php'>Перейти к странице:<br/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
}

echo '<div class="bmenu">Всего он-лайн: ' . $count . '</div>';
require_once ("../incfiles/end.php");

?>