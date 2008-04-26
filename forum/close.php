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

if ($dostfmod == 1)
{
    if (empty($_GET['id']))
    {
        require_once ("../incfiles/head.php");
        echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    $id = intval(check($_GET['id']));

    $typ = mysql_query("select * from `forum` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    if ($ms[type] != "t")
    {
        require_once ("../incfiles/head.php");
        echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    if (isset($_GET['closed']))
    {
        mysql_query("update `forum` set  edit='1' where id='" . $id . "';");
        header("Location: index.php?id=$ms[refid]");
    } else
    {
        mysql_query("update `forum` set  edit='0' where id='" . $id . "';");
        header("Location: index.php?id=$id");
    }
} else
{
    require_once ("../incfiles/head.php");
    echo "Доступ закрыт!!!<br>";
}

?>