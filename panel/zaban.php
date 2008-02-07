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

require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");


if ($dostkmod == 1 || $dostfmod == 1 || $dostcmod == 1)
{
    if (empty($_GET['user']))
    {
        require ("../incfiles/head.php");
        require ("../incfiles/inc.php");
        echo "Вы не ввели логин!<br/><a href='main.php'>В админку</a><br/>";
        require ("../incfiles/end.php");
        exit;
    }


    $user = intval(check($_GET['user']));
    $q = @mysql_query("select * from `users` where id='" . $user . "';");
    $nme = @mysql_fetch_array($q);
    $nam = trim($nme[name]);


    if (($login !== $nickadmina) && ($nam == $nickadmina) || ($login !== $nickadmina2) && ($nam == $nickadmina2) || ($nam !== $login) && ($nme[rights] >= "1"))
    {
        require ("../incfiles/head.php");
        require ("../incfiles/inc.php");
        echo "У ВАС НЕДОСТАТОЧНО ПРАВ ДЛЯ ЭТОГО!<br/>";
        require ("../incfiles/end.php");
        exit;
    }


    if (isset($_POST['submit']))
    {


        $user = intval(check($_GET['user']));


        if ($_POST['offban'] == 1)
        {
            mysql_query("update `users` set ban='0' where id='" . $user . "';");
        }
        if ($_POST['foffban'] == 1)
        {
            mysql_query("update `users` set fban='0' where id='" . $user . "';");
        }
        if ($_POST['coffban'] == 1)
        {
            mysql_query("update `users` set chban='0' where id='" . $user . "';");
        }

        if ($_POST['onban'] == 1)
        {

            $whyban = check($_POST['whyban']);
            $mod = intval(check($_POST['mod']));
            $vrem = intval(check($_POST['vrem']));

            switch ($mod)
            {
                case "1":
                    if ($dostfmod != 1)
                    {
                        require ("../incfiles/head.php");
                        require ("../incfiles/inc.php");
                        echo "Ошибка<br/><a href='main.php'>В админку</a><br/>";
                        require ("../incfiles/end.php");
                        exit;
                    }
                    if (empty($vrem) || $vrem > 60)
                    {
                        $vrem = 60;
                    }
                    $vrem = round($realtime + ($vrem * 60));
                    mysql_query("update `users` set  ftime='" . $vrem . "',fwhy='" . $whyban . "',fwho='" . $login . "',fban='1',time='" . $realtime . "'  where id='" . $user . "';");

                    break;

                case "2":
                    if ($dostcmod != 1)
                    {
                        require ("../incfiles/head.php");
                        require ("../incfiles/inc.php");
                        echo "Ошибка<br/><a href='main.php'>В админку</a><br/>";
                        require ("../incfiles/end.php");
                        exit;
                    }
                    if (empty($vrem) || $vrem > 60)
                    {
                        $vrem = 60;
                    }
                    $vrem = round($realtime + ($vrem * 60));
                    mysql_query("update `users` set `chtime`='" . $vrem . "',`chwhy`='" . $whyban . "',`chwho`='" . $login . "',`chban`='1',`time`='" . $realtime . "'  where `id`='" . $user . "';");

                    break;

                case "3":
                    if ($dostkmod != 1)
                    {
                        require ("../incfiles/head.php");
                        require ("../incfiles/inc.php");
                        echo "Ошибка<br/><a href='main.php'>В админку</a><br/>";
                        require ("../incfiles/end.php");
                        exit;
                    }
                    if ($dostadm == 1)
                    {
                        if (empty($vrem))
                        {
                            $ban = 2;
                        } else
                        {
                            $ban = 1;
                        }
                    } else
                    {
                        $ban = 1;
                        if (empty($vrem) || $vrem > 2880)
                        {
                            $vrem = 2880;
                        }
                    }
                    $vrem = round($realtime + ($vrem * 60));


                    mysql_query("update `users` set `bantime`='" . $vrem . "',`why`='" . $whyban . "',`time`='" . $realtime . "',`who`='" . $login . "',`ban`='" . $ban . "' where `id`='" . $user . "';");


                    break;
            }

            mysql_query("insert into `bann` values('" . $nam . "','','','" . $login . "', '" . $realtime . "','" . $whyban . "','yes','" . $mod . "');");
        } else
        {

        }

        header("Location: zaban.php?user=$user&ok");

    } else
    {
        require ("../incfiles/head.php");
        require ("../incfiles/inc.php");
        $user = intval(check($_GET['user']));

        $q1 = mysql_query("select * from `users` where id='" . $user . "';");
        $arr1 = mysql_fetch_array($q1);
        echo "Наказываем юзера $arr1[name]<br/>";
        if (isset($_GET['ok']))
        {
            echo "Профиль изменён!<br/>";
        }
        if ($nam == $login)
        {
            echo 'С УМА СОШЁЛ?САМОГО СЕБЯ В БАНЮ?<br/>';
        }
        echo "<form action='zaban.php?user=" . $user . "' method='post'>";

        if (($nme[ban] == "1" && $nme[bantime] > $realtime) || $nme[ban] == "2")
        {
            echo "Внимание, юзер находится в бане!<br/>Забанил: $nme[who]<br/>Причина ";
            if (!empty($nme[why]))
            {
                echo ": $nme[why]<br/>";
            } else
            {
                echo "не указана<br/>";
            }
            if ($nme[ban] == "2")
            {
                echo "Бан активен до отмены<br/>";
            }
            if ($nme[ban] == "1")
            {
                $tti = round(($nme[bantime] - $realtime) / 60);
                if ($tti > 60)
                {
                    $jjtti = round($tti / 60) . ' час.';
                } else
                {
                    $jjtti = $tti . ' мин';
                }
                echo "До окончания бана осталось: $jjtti<br/>";
            }

            if ($dostkmod == 1)
            {
                echo "Разбанить<input type='checkbox' name='offban' value='1'/><br/>";
            }
        }

        if ($nme[fban] == "1" && $nme[ftime] > $realtime)
        {
            echo "Внимание, юзера пнули из форума!<br/>Пнул: $nme[fwho]<br/>Причина ";
            if (!empty($nme[fwhy]))
            {
                echo ": $nme[fwhy]<br/>";
            } else
            {
                echo "не указана<br/>";
            }
            $tti1 = round(($nme[ftime] - $realtime) / 60);

            $jjtti1 = $tti1 . ' мин';
            echo "До окончания осталось: $jjtti1<br/>";

            if ($dostfmod == 1)
            {
                echo "Отменить<input type='checkbox' name='foffban' value='1'/><br/>";
            }
        }

        if ($nme[chban] == "1" && $nme[chtime] > $realtime)
        {
            echo "Внимание, юзера пнули из чата!<br/>Пнул: $nme[chwho]<br/>Причина ";
            if (!empty($nme[chwhy]))
            {
                echo ": $nme[chwhy]<br/>";
            } else
            {
                echo "не указана<br/>";
            }
            $tti2 = round(($nme[chtime] - $realtime) / 60);
            $jjtti2 = $tti2 . ' мин';
            echo "До окончания осталось: $jjtti2<br/>";

            if ($dostcmod == 1)
            {
                echo "Отменить<input type='checkbox' name='coffban' value='1'/><br/>";
            }
        }
        if (isset($_GET['forum']))
        {
            $id = intval(check($_GET['id']));
            $q = mysql_query("select * from `forum` where type='m' and id='" . $id . "';");
            $q1 = mysql_fetch_array($q);
            $vr = date("d.m.y / H:i", $q1[time]);
            $pri = "Пост форума ($vr) $q1[text]";
        }
        if (isset($_GET['chat']))
        {
            $id = intval(check($_GET['id']));
            $q = mysql_query("select * from `chat` where type='m' and id='" . $id . "';");
            $q1 = mysql_fetch_array($q);
            $vr = date("d.m.y / H:i", $q1[time]);
            $pri = "Пост чата ($vr) $q1[text]";
        }
        echo "<hr/>Наказать<input type='checkbox' name='onban' value='1'/><br/><select name='mod'>";
        if ($dostfmod == 1 && ($nme[fban] != 1 || $nme[ftime] < $realtime))
        {
            echo "<option value='1'>Пнуть из форума</option>";
        }
        if ($dostcmod == 1 && ($nme[chban] != 1 || $nme[chtime] < $realtime))
        {
            echo "<option value='2'>Пнуть из чата</option>";
        }
        if ($dostkmod == 1 && ($nme[ban] != 1 || $nme[bantime] < $realtime) && $nme[ban] != 2)
        {
            echo "<option value='3'>Забанить</option>";
        }

        echo "</select><br/>На какое время в минутах:<br/>";
        if ($dostadm == 1)
        {
            echo "(Если не указывать,бан даётся до отмены.<br/>";
        } elseif ($dostkmod == 1)
        {
            echo "(Макс. время бана 48 часов.<br/>";
        } else
        {
            echo "(";
        }
        echo "Макс. время пинка 60 минут):<br/><input type='text' name='vrem'/><br/>Причина бана:<br/>
<input type='text' name='whyban' value='" . $pri . "'/><br/>
<input type='submit' name='submit' value='ok'/></form>";


    }
    echo "<a href='main.php'>В админку</a><br/>";
} else
{
    header("Location: ../index.php?err");

}


require ("../incfiles/end.php");
?>

