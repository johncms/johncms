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

$textl = 'Администрация';
$headmod = "moders";
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");
$adm = mysql_query("select * from `users` where rights='7';");
$adm2 = mysql_num_rows($adm);
if ($adm2 != 0)
{
    echo "<p><b>Администраторы:</b><br/>";
    while ($adm1 = mysql_fetch_array($adm))
    {
        $user = mysql_query("select * from `users` where id='" . $adm1[id] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['uid'])) && ($login != $udat[name]))
        {
            echo "<a href='anketa.php?user=" . $udat[id] . "'>$udat[name]</a>";
        } else
        {
            echo "&nbsp;&nbsp;$udat[name]";
        }
        $ontime = $udat[lastdate];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo '&nbsp;<font color="#FF0000"> [Off]</font><br/>';
        } else
        {
            echo '&nbsp;<font color="#00AA00"> [ON]</font><br />';
        }
    }
    echo '</p>';
}
$smd = mysql_query("select * from `users` where rights='6';");
$smd2 = mysql_num_rows($smd);
if ($smd2 != 0)
{
    echo "<p><b>Супермодераторы:</b><br/>";
    while ($smd1 = mysql_fetch_array($smd))
    {
        $user = mysql_query("select * from `users` where id='" . $smd1[id] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['uid'])) && ($login != $udat[name]))
        {
            echo "&nbsp;&nbsp;<a href='anketa.php?user=" . $udat[id] . "'>$udat[name]</a>";
        } else
        {
            echo "&nbsp;&nbsp;$udat[name]";
        }
        $ontime = $udat[lastdate];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo '&nbsp;<font color="#FF0000"> [Off]</font><br/>';
        } else
        {
            echo '&nbsp;<font color="#00AA00"> [ON]</font><br />';
        }
    }
    echo '</p>';
}

// Статистика модеров по Библиотеке
$lmd = mysql_query("select * from `users` where rights='5';");
$lmd2 = mysql_num_rows($lmd);
if ($lmd2 != 0)
{
    echo "<p><b>Зам. адм. по библиотеке:</b><br/>";
    while ($lmd1 = mysql_fetch_array($lmd))
    {
        $user = mysql_query("select * from `users` where id='" . $lmd1['id'] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['uid'])) && ($login != $udat['name']))
        {
            echo "&nbsp;&nbsp;<a href='anketa.php?user=" . $udat['id'] . "'>$udat[name]</a>";
        } else
        {
            echo "&nbsp;&nbsp;$udat[name]";
        }
        $ontime = $udat['lastdate'];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo '&nbsp;<font color="#FF0000"> [Off]</font><br/>';
        } else
        {
            echo '&nbsp;<font color="#00AA00"> [ON]</font><br />';
        }
    }
    echo '</p>';
}

// Статистика модеров по загрузкам
$dmd = mysql_query("select * from `users` where rights='4';");
$dmd2 = mysql_num_rows($dmd);
if ($dmd2 != 0)
{
    echo "<p><b>Зам. адм. по загрузкам:</b><br/>";
    while ($dmd1 = mysql_fetch_array($dmd))
    {
        $user = mysql_query("select * from `users` where id='" . $dmd1['id'] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['uid'])) && ($login != $udat['name']))
        {
            echo "&nbsp;&nbsp;<a href='anketa.php?user=" . $udat['id'] . "'>$udat[name]</a>";
        } else
        {
            echo "&nbsp;&nbsp;$udat[name]";
        }
        $ontime = $udat['lastdate'];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo '&nbsp;<font color="#FF0000"> [Off]</font><br/>';
        } else
        {
            echo '&nbsp;<font color="#00AA00"> [ON]</font><br />';
        }
    }
    echo '</p>';
}

// Статистика по Модерам Форума
$fmd = mysql_query("select * from `users` where rights='3';");
$fmd2 = mysql_num_rows($fmd);
if ($fmd2 != 0)
{
    echo "<p><b>Модеры форума:</b><br/>";
    while ($fmd1 = mysql_fetch_array($fmd))
    {
        $user = mysql_query("select * from `users` where id='" . $fmd1['id'] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['uid'])) && ($login != $udat['name']))
        {
            echo "&nbsp;&nbsp;<a href='anketa.php?user=" . $udat['id'] . "'>$udat[name]</a>";
        } else
        {
            echo "&nbsp;&nbsp;$udat[name]";
        }
        $ontime = $udat['lastdate'];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo '&nbsp;<font color="#FF0000"> [Off]</font><br/>';
        } else
        {
            echo '&nbsp;<font color="#00AA00"> [ON]</font><br />';
        }
    }
    echo '</p>';
}

// Статистика по Модерам Чата
$cmd = mysql_query("select * from `users` where rights='2';");
$cmd2 = mysql_num_rows($cmd);
if ($cmd2 != 0)
{
    echo "<p><b>Модеры чата:</b><br/>";
    while ($cmd1 = mysql_fetch_array($cmd))
    {
        $user = mysql_query("select * from `users` where id='" . $cmd1['id'] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['uid'])) && ($login != $udat['name']))
        {
            echo "&nbsp;&nbsp;<a href='anketa.php?user=" . $udat['id'] . "'>$udat[name]</a>";
        } else
        {
            echo "&nbsp;&nbsp;$udat[name]";
        }
        $ontime = $udat['lastdate'];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo '&nbsp;<font color="#FF0000"> [Off]</font><br/>';
        } else
        {
            echo '&nbsp;<font color="#00AA00"> [ON]</font><br />';
        }
    }
    echo '</p>';
}

// Статистика по Киллерам
$kmd = mysql_query("select * from `users` where rights='1';");
$kmd2 = mysql_num_rows($kmd);
if ($kmd2 != 0)
{
    echo "<p><b>Киллеры:</b><br/>";
    while ($kmd1 = mysql_fetch_array($kmd))
    {
        $user = mysql_query("select * from `users` where id='" . $kmd1['id'] . "';");
        $udat = mysql_fetch_array($user);
        if ((!empty($_SESSION['uid'])) && ($login != $udat['name']))
        {
            echo "&nbsp;&nbsp;<a href='anketa.php?user=" . $udat['id'] . "'>$udat[name]</a>";
        } else
        {
            echo "&nbsp;&nbsp;$udat[name]";
        }
        $ontime = $udat['lastdate'];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo '&nbsp;<font color="#FF0000"> [Off]</font><br/>';
        } else
        {
            echo '&nbsp;<font color="#00AA00"> [ON]</font><br />';
        }
    }
    echo '</p>';
}

require_once ("../incfiles/end.php");

?>