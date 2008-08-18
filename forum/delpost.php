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
    $id = intval($_GET['id']);
    $typ = mysql_query("select * from `forum` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    if ($ms['type'] != "m")
    {
        require_once ("../incfiles/head.php");
        echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    if (isset($_GET['yes']))
    {
        if ($dostsadm == 1)
        {
            if (!empty($ms['attach']))
            {
                unlink("files/$ms[attach]");
            }
            mysql_query("delete from `forum` where `id`='" . $id . "';");
        } else
        {
            mysql_query("update `forum` set  close='1' where id='" . $id . "';");
        }
        header("Location: index.php?id=$ms[refid]");
    }
    if (isset($_GET['hid']))
    {
        if ($dostsadm == 1)
        {
            mysql_query("update `forum` set  close='1' where id='" . $id . "';");
        }
        header("Location: index.php?id=$ms[refid]");
    }
    require_once ("../incfiles/head.php");
    echo '<p>Вы действительно хотите удалить пост?</p>';
    echo '<p><a href="?act=delpost&amp;id=' . $id . '&amp;yes">Удалить</a><br />';
    if (($dostsadm == 1) && ($ms['close'] != 1))
    {
        echo '<a href="index.php?act=delpost&amp;id=' . $id . '&amp;hid">Скрыть</a><br />';
    }
    echo '<a href="index.php?id=' . $ms['refid'] . '">Отмена</a></p>';
} else
{
    echo "Доступ закрыт!!!<br>";
}

?>