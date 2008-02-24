<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC2                                                        //
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


$textl = 'Кто в форуме?';
$headmod = "forum";
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");
require ("../incfiles/head.php");
require ("../incfiles/inc.php");
if (!empty($_SESSION['pid']))
{
    $tti = round(($datauser['ftime'] - $realtime) / 60);
    if ($datauser['fban'] == "1" && $tti > 0)
    {

        echo "Вас пнули из форума<br/>Кто: <font color='red'>$datauser[fwho]</font><br/>";
        if ($datauser[fwhy] == "")
        {
            echo "<div>Причина не указана</div>";
        } else
        {
            echo "Причина:<font color='red'> $datauser[fwhy]</font><br>";
        }
        echo "Время до окончания: $tti минут<br/>";
        require ("../incfiles/end.php");
        exit;
    }
}
if (empty($_SESSION['pid']))
{
    echo "Вы не авторизованы!<br/>";
    require ("../incfiles/end.php");
    exit;
}
if (!empty($_GET['id']))
{
    $id = intval(check($_GET['id']));
    $typ = mysql_query("select * from `forum` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    if ($ms[type] != "t")
    {
        echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
        require ("../incfiles/end.php");
        exit;
    }
    echo "Кто в теме <font color='#FF0000'>$ms[text]</font><hr/>";
    $onltime = $realtime - 300;
    $whr = "forum,$id";
    $q = @mysql_query("select * from `users` where  lastdate>='" . intval($onltime) . "';");
    while ($arr = mysql_fetch_array($q))
    {
        $wh = mysql_query("select * from `count` where name='" . $arr[name] . "' order by time desc ;");
        while ($wh1 = mysql_fetch_array($wh))
        {
            $wh2[] = $wh1[where];
        }
        $wher = $wh2[0];
        $wh2 = array();
        if ($wher == $whr)
        {
            echo "<b>$arr[name]</b>";
            switch ($arr[rights])
            {
                case 7:
                    echo ' Adm ';
                    break;
                case 6:
                    echo ' Smd ';
                    break;
                case 3:
                    echo ' Mod ';
                    break;
                case 1:
                    echo ' Kil ';
                    break;
            }
            echo "<br/>";
            $i++;
        }
    }
    echo "<hr/>В теме $i человек<br/>";
    echo "<a href='index.php?id=" . $id . "'>В тему</a><br/>";
} else
{
    echo "Кто в форуме<hr/>";
    $onltime = $realtime - 300;
    $qf = @mysql_query("select * from `users` where  lastdate>='" . intval($onltime) . "';");
    while ($arrf = mysql_fetch_array($qf))
    {
        $whf = mysql_query("select * from `count` where name='" . $arrf[name] . "' order by time desc ;");
        while ($whf1 = mysql_fetch_array($whf))
        {
            $whf2[] = $whf1[where];
        }
        $wherf = $whf2[0];
        $whf2 = array();
        $wherf1 = explode(",", $wherf);
        if ($wherf1[0] == "forum")
        {
            $count = $count + 1;
        }
    }

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


    $q = @mysql_query("select * from `users` where  lastdate>='" . intval($onltime) . "';");

    $i = 0;
    while ($arr = mysql_fetch_array($q))
    {

        $wh = mysql_query("select * from `count` where name='" . $arr[name] . "' order by time desc ;");

        while ($wh1 = mysql_fetch_array($wh))
        {
            $wh2[] = $wh1[where];
        }
        $wher = $wh2[0];
        $wh2 = array();
        $wher1 = explode(",", $wher);
        if ($wher1[0] == "forum")
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
                echo "$div";
                if (empty($wher1[1]))
                {
                    $adres = "<a href='index.php'>На главной форума</a>";
                } else
                {
                    $adr = mysql_query("select * from `forum` where id='" . $wher1[1] . "';");
                    $adr1 = mysql_fetch_array($adr);
                    ###
                    switch ($adr1[type])
                    {
                        case "m":
                            $q2 = mysql_query("select * from `forum` where type='t' and id='" . $adr1[refid] . "';");
                            $tem = mysql_fetch_array($q2);
                            $q3 = mysql_query("select * from `forum` where type='r' and id='" . $tem[refid] . "';");
                            $razd = mysql_fetch_array($q3);
                            $q4 = mysql_query("select * from `forum` where type='f' and id='" . $razd[refid] . "';");
                            $frm = mysql_fetch_array($q4);

                            if ($tem[close] == 1)
                            {
                                $adres = "<a href='index.php'>На главной форума</a>";
                            } else
                            {
                                $adres = "<a href='index.php?id=" . $tem[id] . "'>$frm[text]/$razd[text]/$tem[text]</a>";
                            }
                            break;
                        case "t":
                            $q3 = mysql_query("select * from `forum` where type='r' and id='" . $adr1[refid] . "';");
                            $razd = mysql_fetch_array($q3);
                            $q4 = mysql_query("select * from `forum` where type='f' and id='" . $razd[refid] . "';");
                            $frm = mysql_fetch_array($q4);
                            if ($adr1[close] == 1)
                            {
                                $adres = "<a href='index.php'>На главной форума</a>";
                            } else
                            {
                                $adres = "<a href='index.php?id=" . $adr1[id] . "'>$frm[text]/$razd[text]/$adr1[text]</a>";
                            }
                            break;
                        case "r":
                            $q4 = mysql_query("select * from `forum` where type='f' and id='" . $adr1[refid] . "';");
                            $frm = mysql_fetch_array($q4);
                            $adres = "<a href='index.php?id=" . $adr1[id] . "'>$frm[text]/$adr1[text]</a>";
                            break;
                        case "f":
                            $adres = "<a href='index.php?id=" . $adr1[id] . "'>$adr1[text]</a>";
                            break;
                    }
                }
                ###
                echo "<b>$arr[name]</b>";
                switch ($arr[rights])
                {
                    case 7:
                        echo ' Adm ';
                        break;
                    case 6:
                        echo ' Smd ';
                        break;
                    case 3:
                        echo ' Mod ';
                        break;
                    case 1:
                        echo ' Kil ';
                        break;
                }
                echo "<br/>$adres</div>";
            }
            $i++;
        }
    }
    ##
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
            echo '<a href="who.php?id=' . $id . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
        }
        if ($offpg != 1)
        {
            if ($asd < $count && $asd > 0)
            {
                echo ' <a href="who.php?id=' . $id . '&amp;page=1&amp;">1</a> .. ';
            }
            $page2 = $ba - $page;
            $pa = ceil($page / 2);
            $paa = ceil($page / 3);
            $pa2 = $page + floor($page2 / 2);
            $paa2 = $page + floor($page2 / 3);
            $paa3 = $page + (floor($page2 / 3) * 2);
            if ($page > 13)
            {
                echo ' <a href="who.php?id=' . $id . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="who.php?id=' . $id . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="who.php?id=' . $id . '&amp;page=' . ($paa * 2) . '">' . ($paa * 2) .
                    '</a> <a href="who.php?id=' . $id . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
            } elseif ($page > 7)
            {
                echo ' <a href="who.php?id=' . $id . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="who.php?id=' . $id . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
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
                        echo ' <a href="who.php?id=' . $id . '&amp;page=' . $ii . '">' . $ii . '</a> ';
                    }
                }
                $i = $i + 10;
            }
            if ($page2 > 12)
            {
                echo ' .. <a href="who.php?id=' . $id . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="who.php?id=' . $id . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="who.php?id=' . $id . '&amp;page=' . ($paa3) . '">' . ($paa3) .
                    '</a> <a href="who.php?id=' . $id . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
            } elseif ($page2 > 6)
            {
                echo ' .. <a href="who.php?id=' . $id . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="who.php?id=' . $id . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
            }
            if ($asd2 < $count)
            {
                echo ' .. <a href="who.php?id=' . $id . '&amp;page=' . $ba . '">' . $ba . '</a>';
            }
        } else
        {
            echo "<b>[$page]</b>";
        }


        if ($count > $start + 10)
        {
            echo ' <a href="who.php?id=' . $id . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
        }
        echo "<form action='pradd.php'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
            "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
    }

    ###

    echo "<hr/>В форуме $count человек<br/>";
    echo "<a href='index.php'>В форум</a><br/>";
}


require ("../incfiles/end.php");
?>