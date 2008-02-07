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

$textl = 'Администрация';
$headmod = "moders";
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");
require ("../incfiles/head.php");
require ("../incfiles/inc.php");
echo "<b>Старшие на сайте</b><hr/>";
$adm = mysql_query("select * from `users` where rights='7';");
$adm2 = mysql_num_rows($adm);
if ($adm2 != 0)
{
    echo "Администраторы :<br/><br/>";
    while ($adm1 = mysql_fetch_array($adm))
    {
        $user = mysql_query("select * from `users` where id='" . $adm1[id] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['pid'])) && ($login != $udat[name]))
        {
            echo "<a href='anketa.php?user=" . $udat[id] . "'>$udat[name]</a>";
        } else
        {
            echo "$udat[name]";
        }
        $ontime = $udat[lastdate];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo " [Off]<br/>";
        } else
        {
            echo " [ON]<br/>";
        }
    }
    echo "<hr/>";
}
$smd = mysql_query("select * from `users` where rights='6';");
$smd2 = mysql_num_rows($smd);
if ($smd2 != 0)
{
    echo "Супермодераторы :<br/><br/>";
    while ($smd1 = mysql_fetch_array($smd))
    {
        $user = mysql_query("select * from `users` where id='" . $smd1[id] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['pid'])) && ($login != $udat[name]))
        {
            echo "<a href='anketa.php?user=" . $udat[id] . "'>$udat[name]</a>";
        } else
        {
            echo "$udat[name]";
        }
        $ontime = $udat[lastdate];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo " [Off]<br/>";
        } else
        {
            echo " [ON]<br/>";
        }
    }
    echo "<hr/>";
}
$lmd = mysql_query("select * from `users` where rights='5';");
$lmd2 = mysql_num_rows($lmd);
if ($lmd2 != 0)
{
    echo "Зам. адм. по библиотеке :<br/><br/>";
    while ($lmd1 = mysql_fetch_array($lmd))
    {
        $user = mysql_query("select * from `users` where id='" . $lmd1[id] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['pid'])) && ($login != $udat[name]))
        {
            echo "<a href='anketa.php?user=" . $udat[id] . "'>$udat[name]</a>";
        } else
        {
            echo "$udat[name]";
        }
        $ontime = $udat[lastdate];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo " [Off]<br/>";
        } else
        {
            echo " [ON]<br/>";
        }
    }
    echo "<hr/>";
}
$dmd = mysql_query("select * from `users` where rights='4';");
$dmd2 = mysql_num_rows($dmd);
if ($dmd2 != 0)
{
    echo "Зам. адм. по загрузкам :<br/><br/>";
    while ($dmd1 = mysql_fetch_array($dmd))
    {
        $user = mysql_query("select * from `users` where id='" . $dmd1[id] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['pid'])) && ($login != $udat[name]))
        {
            echo "<a href='anketa.php?user=" . $udat[id] . "'>$udat[name]</a>";
        } else
        {
            echo "$udat[name]";
        }
        $ontime = $udat[lastdate];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo " [Off]<br/>";
        } else
        {
            echo " [ON]<br/>";
        }
    }
    echo "<hr/>";
}
$fmd = mysql_query("select * from `users` where rights='3';");
$fmd2 = mysql_num_rows($fmd);
if ($fmd2 != 0)
{
    echo "Модеры форума :<br/><br/>";
    while ($fmd1 = mysql_fetch_array($fmd))
    {
        $user = mysql_query("select * from `users` where id='" . $fmd1[id] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['pid'])) && ($login != $udat[name]))
        {
            echo "<a href='anketa.php?user=" . $udat[id] . "'>$udat[name]</a>";
        } else
        {
            echo "$udat[name]";
        }
        $ontime = $udat[lastdate];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo " [Off]<br/>";
        } else
        {
            echo " [ON]<br/>";
        }
    }
    echo "<hr/>";
}
$cmd = mysql_query("select * from `users` where rights='2';");
$cmd2 = mysql_num_rows($cmd);
if ($cmd2 != 0)
{
    echo "Модеры чата :<br/><br/>";
    while ($cmd1 = mysql_fetch_array($cmd))
    {
        $user = mysql_query("select * from `users` where id='" . $cmd1[id] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['pid'])) && ($login != $udat[name]))
        {
            echo "<a href='anketa.php?user=" . $udat[id] . "'>$udat[name]</a>";
        } else
        {
            echo "$udat[name]";
        }
        $ontime = $udat[lastdate];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo " [Off]<br/>";
        } else
        {
            echo " [ON]<br/>";
        }
    }
    echo "<hr/>";
}
$kmd = mysql_query("select * from `users` where rights='1';");
$kmd2 = mysql_num_rows($kmd);
if ($kmd2 != 0)
{
    echo "Киллеры :<br/><br/>";
    while ($kmd1 = mysql_fetch_array($kmd))
    {
        $user = mysql_query("select * from `users` where id='" . $kmd1[id] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['pid'])) && ($login != $udat[name]))
        {
            echo "<a href='anketa.php?user=" . $udat[id] . "'>$udat[name]</a>";
        } else
        {
            echo "$udat[name]";
        }
        $ontime = $udat[lastdate];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo " [Off]<br/>";
        } else
        {
            echo " [ON]<br/>";
        }
    }
    echo "<hr/>";
}
require ("../incfiles/end.php");
?>