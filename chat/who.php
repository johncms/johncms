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

$textl = 'Кто в чате';
$headmod = "chat";
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");
require ("../incfiles/head.php");
require ("../incfiles/inc.php");

if (!empty($_SESSION['pid']))
{
    $tti = round(($datauser['chtime'] - $realtime) / 60);
    if ($datauser['chban'] == "1" && $tti > 0)
    {
        require ("../incfiles/head.php");
        require ("../incfiles/inc.php");
        echo "Вас пнули из чата<br/>Кто: <font color='" . $cdinf . "'>$datauser[chwho]</font><br/>";
        if ($datauser[chwhy] == "")
        {
            echo "<div>Причина не указана</div>";
        } else
        {
            echo "Причина:<font color='" . $cdinf . "'> $datauser[chwhy]</font><br/>";
        }
        echo "Время до окончания: $tti минут<br/>";
        require ("../incfiles/end.php");
        exit;
    }
    if (!empty($_GET['id']))
    {
        $id = intval(check($_GET['id']));
        $typ = mysql_query("select * from `chat` where id='" . $id . "';");
        $ms = mysql_fetch_array($typ);
        if ($ms[type] != "r")
        {
            echo "Ошибка!<br/><a href='index.php?'>В чат</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        echo "Кто в комнате <font color='" . $cntem . "'>$ms[text]</font><hr/>";
        $onltime = $realtime - 300;
        $whr = "chat,$id";
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
                if ($ms[dpar] != "in")
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
                        case 2:
                            echo ' Mod ';
                            break;
                        case 1:
                            echo ' Kil ';
                            break;
                    }
                    echo "<br/>";
                }
                $i++;
            }
        }
        echo "<hr/>В комнате $i человек<br/>";
        echo "<a href='index.php?id=" . $id . "'>Назад</a><br/>";
    } else
    {
        echo "В прихожей:<br/>";
        $onltime = $realtime - 300;
        $wc = "chat";
        $q1 = @mysql_query("select * from `users` where  lastdate>='" . intval($onltime) . "';");
        while ($arr1 = mysql_fetch_array($q1))
        {
            $wh = mysql_query("select * from `count` where name='" . $arr1[name] . "' order by time desc ;");
            while ($wh1 = mysql_fetch_array($wh))
            {
                $wh2[] = $wh1[where];
            }
            $wher = $wh2[0];
            $wh2 = array();
            if ($wher == $wc)
            {
                $uz[] = $arr1[name];
            }
        }
        $c = count($uz);
        if ($c != 0)
        {
            $uz1 = implode(",", $uz);
            echo "$uz1<br/>";
        } else
        {
            echo "Никого...<br/>";
        }
        $uz = array();
        $kom = mysql_query("select * from `chat` where type='r' order by realid ;");
        while ($mass = mysql_fetch_array($kom))
        {
            echo "<a href='index.php?id=" . $mass[id] . "'><font color='" . $cntem . "'>$mass[text]</font></a><br/>";


            $whr = "chat,$mass[id]";
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
                    $uz[] = $arr[name];
                }
            }
            $c = count($uz);
            if ($c != 0)
            {
                $uz1 = implode(",", $uz);
                if ($mass[dpar] != "in")
                {
                    echo "$uz1<br/>";
                } else
                {
                    echo "Всего: $c<br/>";
                }
            } else
            {
                echo "Никого...<br/>";
            }
            $uz = array();
        }
    }
    echo "<hr/><a href='index.php'>В чат</a><br/>";
} else
{
    echo "Вы не авторизованы!<br/>";
}
require ("../incfiles/end.php");

?>