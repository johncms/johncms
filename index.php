<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC1                                                        //
// Дата релиза: 08.02.2008                                                    //
// Авторский сайт: http://gazenwagen.com                                      //
////////////////////////////////////////////////////////////////////////////////
// Оригинальная идея и код: Евгений Рябинин aka JOHN77                        //
// E-mail: john773@yandex.ru                                                  //
// Модификация, оптимизация и дизайн: Олег Касьянов aka AlkatraZ              //
// E-mail: alkatraz@batumi.biz                                                //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
// Внимание!                                                                  //
// Авторские версии данных скриптов публикуются ИСКЛЮЧИТЕЛЬНО на сайте        //
// http://gazenwagen.com                                                      //
// На этом же сайте оказывается техническая поддержка                         //
// Если Вы скачали данный скрипт с другого сайта, то его работа не            //
// гарантируется и поддержка не оказывается.                                  //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_PUSTO', 1);

$headmod = "mainpage";
require_once ("incfiles/db.php");
require_once ("incfiles/func.php");
require_once ("incfiles/data.php");
require_once ("incfiles/stat.php");
require_once ("incfiles/head.php");
require_once ("incfiles/inc.php");

if (empty($_SESSION['pid']) && empty($_SESSION['provc']) && !empty($_COOKIE['cpide']) && !empty($_COOKIE['ckode']))
{
    $unid = base64_decode($_COOKIE['cpide']);
    $unkod = base64_decode($_COOKIE['ckode']);

    $qp = mysql_query("select * from `users` where id='" . intval($unid) . "';");
    $qpp = mysql_num_rows($qp);
    if ($qpp == 1 && $unkod == $provkode)
    {
        $_SESSION['pid'] = intval($unid);
        $_SESSION['provc'] = intval($unkod);
        mysql_query("update `users` set sestime='" . $realtime . "' where id='" . intval($_SESSION['pid']) . "';");
        header("Location: index.php?enter");
        exit;
    }
}

$reg = mysql_query("select * from `users` where id='" . intval($_SESSION['pid']) . "';");
$reg1 = mysql_fetch_array($reg);
$regadmin = trim($reg1['regadm']);
if (isset($_GET['enter']))
{
    mysql_query("update `users` set lastdate='" . $realtime . "', ip='" . $ipp . "', browser='" . $agn . "' where id='" . intval($_SESSION['pid']) . "';");


    if (isset($_GET['regprin']))
    {
        $reg = mysql_query("select * from `users` where id='" . intval($_SESSION['pid']) . "';");
        $reg1 = mysql_fetch_array($reg);
        if (!empty($reg1[regadm]))
        {
            $regadmin = trim($reg1[regadm]);
            print ("<div style='text-align: center'>Ваша заявка на регистрацию одобрена администратором $regadmin</div>");
        }
    }

    echo "Привет,$login !<br/>";
    if ($dayr == $day && $monthr == $mon)
    {
        echo "<font color = 'red'><b>С ДНЁМ РОЖДЕНИЯ!!!</b></font><br/>";
    }
    $birth = mysql_query("select * from `users` where preg='1' and dayb='" . $day . "' and monthb='" . $mon . "';");
    $brd = mysql_num_rows($birth);
    if ($brd > 0)
    {
        echo "Сегодня <a href='str/brd.php'>" . $brd . "</a> именинников<br/>";
    }

    if (!isset($_GET['regprin']) && !empty($datauser[lastdate]))
    {
        echo "Последний раз вы заходили сюда $dpp с IP адреса $ipadr и браузера $soft<br/>";

        echo "За это время новостей";
        $nw = mysql_query("select * from `news` where
time>'" . $datauser[lastdate] . "';");
        $kv = mysql_num_rows($nw);
        if ($kv != 0)
        {
            echo "<a href='str/news.php?kv=" . $kv . "'>: $kv</a><br/>";
        } else
        {
            echo " нет.<br/>";
        }


        ##########
        $lp = mysql_query("select * from `forum` where type='t' and moder='1' and close!='1';");

        while ($arrt = mysql_fetch_array($lp))
        {
            $q3 = mysql_query("select * from `forum` where type='r' and id='" . $arrt[refid] . "';");
            $q4 = mysql_fetch_array($q3);
            $rz = mysql_query("select * from `forum` where type='n' and refid='" . $q4[refid] . "' and `from`='" . $login . "';");
            $np = mysql_query("select * from `forum` where type='l' and time>='" . $arrt[time] . "' and refid='" . $arrt[id] . "' and `from`='" . $login . "';");
            if ((mysql_num_rows($np)) != 1 && (mysql_num_rows($rz)) != 1)
            {
                $knt = $knt + 1;
            }
        }
        if ($knt != 0)
        {
            $knt = "Непрочитанных тем на форуме: <a href='forum/new.php'>$knt</a>";
        }
        echo "$knt<br/>";

    } else
    {
        echo "Добро пожаловать на $copyright !<br/>";
    }
    echo "<a href='" . $home . "'>На главную</a></div></body></html>";
    exit;
}


if (isset($_GET['err']))
{
    print ("<div style='text-align: center'>Ошибка 404: файл не найден!!!</div>");
}
if (isset($_GET['nolog']))
{
    print ("<div style='text-align: center'>Ошибка:такой логин не зарегистрирован!!!</div>");
}
if (isset($_GET['regwait']))
{
    print ("<div style='text-align: center'>Сорри,ваша заявка ещё не рассмотрена,ожидайте</div>");
}
if (isset($_GET['regotkl']))
{
    $regadmin = check($_GET['regadmin']);
    print ("<div style='text-align: center'>Ваша заявка на регистрацию отклонена администратором " . $regadmin . "</div>");
}
mysql_query("update `users` set lastdate='" . $realtime . "', ip='" . $ipp . "', browser='" . $agn . "' where id='" . intval($_SESSION['pid']) . "';");
include "pages/mainmenu.php";
require ("incfiles/end.php");

?>