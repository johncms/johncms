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

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($dostlmod == 1)
{
    echo "<br/>Модерация статей<br/>";
    if ((!empty($_GET['id'])) && (isset($_GET['yes'])))
    {
        $id = intval(trim($_GET['id']));
        mysql_query("update `lib` set moder='1' , time='" . $realtime . "' where id='" . $id . "';");
        $fn = mysql_query("select `id`, `text` from `lib` where id='" . $id . "';");
        $fn1 = mysql_fetch_array($fn);
        echo "Статья $fn1[name] добавлена в базу<br/>";
    }
    if (isset($_GET['all']))
    {
        $mod = mysql_query("select `id` from `lib` where type='bk' and moder='0' ;");
        while ($modadd = mysql_fetch_array($mod))
        {
            mysql_query("update `lib` set moder='1' , time='" . $realtime . "' where id='" . $modadd[id] . "';");
        }
        echo "Все файлы добавлены в базу<br/>";
    }
    $mdz = mysql_query("select `id` from `lib` where type='bk' and moder='0' ;");
    $mdz1 = mysql_num_rows($mdz);
    $ba = ceil($mdz1 / 10);
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
    $start = $page * 10 - 10;
    if ($mdz1 < $start + 10)
    {
        $end = $mdz1;
    } else
    {
        $end = $start + 10;
    }
    if ($mdz1 != 0)
    {
        $md = mysql_query("select `id`, `refid`, `avtor`, `text`, `soft`, `name`, `time` from `lib` where type='bk' and moder='0' LIMIT " . $start . "," . $end . ";");
        while ($md2 = mysql_fetch_array($md))
        {
            $d = $i / 2;
            $d1 = ceil($d);
            $d2 = $d1 - $d;
            $d3 = ceil($d2);
            if ($d3 == 0)
            {
                $div = "<div class='c'>";
            } else
            {
                $div = "<div class='b'>";
            }
            $vr = $md2[time] + $sdvig * 3600;
            $vr = date("d.m.y / H:i", $vr);
            $tx = $md2[soft];
            echo "$div<a href='index.php?id=" . $md2[id] . "'>$md2[name]</a><br/>Добавил: $md2[avtor] ($vr)<br/>$tx <br/>";
            $nadir = $md2['refid'];
            $pat = "";
            while ($nadir != "0")
            {
                $dnew = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $nadir . "';");
                $dnew1 = mysql_fetch_array($dnew);
                $pat = "$dnew1[text]/$pat";
                $nadir = $dnew1['refid'];
            }
            $l = mb_strlen($pat);
            $pat1 = mb_substr($pat, 0, $l - 1);
            echo "[$pat1]<br/><a href='index.php?act=moder&amp;id=" . $md2['id'] . "&amp;yes'> Принять</a></div>";
            ++$i;
        }
        if ($md1 > 10)
        {
            echo "<hr/>";
            if ($offpg != 1)
            {
                echo "Страницы:<br/>";
            } else
            {
                echo "Страниц: $ba<br/>";
            }
            if ($start != 0)
            {
                echo '<a href="index.php?act=moder&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
            }
            if ($offpg != 1)
            {
                navigate('index.php?act=moder', $mdz1, 10, $start, $page);
            } else
            {
                echo "<b>[$page]</b>";
            }
            if ($md1 > $start + 10)
            {
                echo ' <a href="index.php?act=moder&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
            }
            echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='act' value='moder'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
        }
        if ($md1 >= 1)
        {
            echo "<br/>Всего: $mdz1";
        }
        echo "<br/><a href='index.php?act=moder&amp;all'>Принять все!</a><br/>";
    }
} else
{
    echo "Нет доступа!<br/>";
}
echo "<a href='?'>К категориям</a><br/>";

?>