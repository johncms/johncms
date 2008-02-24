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

$textl = 'Забаненные';
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");
require ("../incfiles/head.php");
require ("../incfiles/inc.php");
if ($dostmod == 1)
{
    if (!empty($_GET['act']))
    {
        $act = check($_GET['act']);
    }
    switch ($act)
    {
        case "add":
            if ($dostkmod == 1 || $dostfmod == 1 || $dostcmod == 1)
            {
                if (empty($_POST['nik']))
                {
                    echo "Вы не ввели логин!<br/><a href='main.php>Назад</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                $nik = check($_POST['nik']);
                $q = mysql_query("select * from `users` where name='" . $nik . "';");
                $q2 = mysql_num_rows($q);
                if ($q2 == 0)
                {
                    echo "Нет такого юзера!<br/><a href='main.php'>Назад</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                $q1 = mysql_fetch_array($q);
                header("location: http://sitejohn77/panel/zaban.php?user=$q1[id]");
            } else
            {
                header("location: main.php");
            }

            break;
        default:

            echo "А кто у нас в баньке?<br/>";
            $total = 0;
            $bn = mysql_query("select * from `users` where ban='1' and bantime>'" . $realtime . "';");
            $bn2 = mysql_num_rows($bn);
            if ($bn2 > 0)
            {
                $total = $total + $bn2;
                echo "<hr/>Забаненные на время<br/><br/>";
            }
            while ($bn1 = mysql_fetch_array($bn))
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
                echo "$div <a href='../str/anketa.php?act=ban&amp;user=" . $bn1[id] . "'>$bn1[name]</a><br/>";
                if ($dostkmod == 1)
                {
                    echo "<a href='zaban.php?user=" . $bn1[id] . "'>Разбан</a>";
                }
                if ($dostadm == 1)
                {
                    echo " | <a href='editusers.php?act=del&amp;user=" . $bn1[id] . "'>Удалить</a>";
                }
                echo "</div>";
                ++$i;
            }
            ########
            $bo = mysql_query("select * from `users` where ban='2';");
            $bo2 = mysql_num_rows($bo);
            if ($bo2 > 0)
            {
                $total = $total + $bo2;
                echo "<hr/>Забаненные до отмены<br/><br/>";
            }
            while ($bo1 = mysql_fetch_array($bo))
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
                echo "$div <a href='../str/anketa.php?act=ban&amp;user=" . $bo1[id] . "'>$bo1[name]</a><br/>";
                if ($dostkmod == 1)
                {
                    echo "<a href='zaban.php?user=" . $bo1[id] . "'>Разбан</a>";
                }
                if ($dostadm == 1)
                {
                    echo " | <a href='editusers.php?act=del&amp;user=" . $bo1[id] . "'>Удалить</a>";
                }
                echo "</div>";
                ++$i;
            }
            #########
            $bf = mysql_query("select * from `users` where fban='1' and ftime>'" . $realtime . "';");
            $bf2 = mysql_num_rows($bf);
            if ($bf2 > 0)
            {
                $total = $total + $bf2;
                echo "<hr/>Кого пнули из форума?<br/><br/>";
            }
            while ($bf1 = mysql_fetch_array($bf))
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
                echo "$div <a href='../str/anketa.php?act=kolvo&amp;user=" . $bf1[id] . "'>$bf1[name]</a><br/>";
                if ($dostfmod == 1)
                {
                    echo "<a href='zaban.php?user=" . $bf1[id] . "'>Отменить</a>";
                }
                if ($dostadm == 1)
                {
                    echo " | <a href='editusers.php?act=del&amp;user=" . $bf1[id] . "'>Удалить</a>";
                }
                echo "</div>";
                ++$i;
            }
            #########
            $bc = mysql_query("select * from `users` where chban='1' and chtime>'" . $realtime . "';");
            $bc2 = mysql_num_rows($bc);
            if ($bc2 > 0)
            {
                $total = $total + $bc2;
                echo "<hr/>Кого пнули из чата?<br/><br/>";
            }
            while ($bc1 = mysql_fetch_array($bc))
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
                echo "$div <a href='../str/anketa.php?act=kolvo&amp;user=" . $bc1[id] . "'>$bc1[name]</a><br/>";
                if ($dostcmod == 1)
                {
                    echo "<a href='zaban.php?user=" . $bc1[id] . "'>Отменить</a>";
                }
                if ($dostadm == 1)
                {
                    echo " | <a href='editusers.php?act=del&amp;user=" . $bc1[id] . "'>Удалить</a>";
                }
                echo "</div>";
                ++$i;
            }
            ####
            if ($total == 0)
            {
                echo "По ходу никого...Скукота!..<br/>";
            } else
            {
                echo "<br/>Всего наказанных: $total <br/>";
            }
            if ($dostkmod == 1 || $dostfmod == 1 || $dostcmod == 1)
            {
                echo "<hr/><form action='banned.php?act=add' method='post'>Наказать юзера.<br/>Введите ник:<br/><input type='text' name='nik'/><br/><input type='submit' value='Ok!'/></form><br/>";
            }


            break;
    }
    echo "<a href='main.php'>В админку</a><br/>";
} else
{
    header("location: ../index.php?err");
}
require ("../incfiles/end.php");
?>