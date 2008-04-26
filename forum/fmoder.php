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
    if (!empty($_GET['id']))
    {
        $id = intval(check($_GET['id']));
        $typ = mysql_query("select * from `forum` where id='" . $id . "';");
        $type = mysql_fetch_array($typ);
        if ($type[type] != "t")
        {
            require_once ("../incfiles/head.php");
            echo "Ошибка!<br/><a href='index.php'>В форум</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
        mysql_query("update `forum` set  moder='1' where id='" . $id . "';");
        header("Location: index.php?id=$id");
    } else
    {
        require_once ("../incfiles/head.php");
        echo "Темы, ожидающие модерации<br/>";
        $tm = mysql_query("select * from `forum` where type='t' and moder!='1';");
        $tm1 = mysql_num_rows($tm);
        while ($tm2 = mysql_fetch_array($tm))
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
            echo "$div <a href='index.php?id=" . $tm2[id] . "'>$tm2[text]</a><br/>$tm2[from]</div>";
            ++$i;
        }
        echo "Всего: $tm1<br/>";
    }
} else
{
    require_once ("../incfiles/head.php");
    echo "Доступ закрыт!!!<br>";
}
echo "<a href='index.php?'>В форум</a><br/>";

?>