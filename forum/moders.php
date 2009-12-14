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

$headmod = 'forummod';
require_once ("../incfiles/head.php");
if (empty ($_GET['id'])) {
    echo "<font color='" . $cdinf . "'>Модераторы по подфорумам</font><hr/>";
    $f = mysql_query("select * from `forum` where type='f'  order by realid;");
    while ($f1 = mysql_fetch_array($f)) {
        $mod = mysql_query("select * from `forum` where type='a' and refid='" . $f1['id'] . "';");
        $mod2 = mysql_num_rows($mod);
        if ($mod2 != 0) {
            echo "$f1[text]<br/><br/>";
            while ($mod1 = mysql_fetch_array($mod)) {
                $uz = mysql_query("select * from `users` where name='" . $mod1['from'] . "';");
                $uz1 = mysql_fetch_array($uz);
                if ($uz1['rights'] == 3) {
                    if ((!empty ($_SESSION['uid'])) && ($login != $mod1['from'])) {
                        echo "<a href='../str/anketa.php?id=" . $uz1['id'] . "'><font color='" . $conik . "'>$mod1[from]</font></a>";
                    }
                    else {
                        echo $mod1['from'];
                    }
                    $ontime = $uz1['lastdate'];
                    $ontime2 = $ontime + 300;
                    if ($realtime > $ontime2) {
                        echo "<font color='" . $coffs . "'> [Off]</font><br/>";
                    }
                    else {
                        echo "<font color='" . $cons . "'> [ON]</font><br/>";
                    }
                }
            }
            echo "<hr/>";
        }
    }
}
else {
    $typ = mysql_query("select * from `forum` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    switch ($ms['type']) {
        case "t" :
            $q3 = mysql_query("select * from `forum` where type='r' and id='" . $ms['refid'] . "';");
            $razd = mysql_fetch_array($q3);
            $q4 = mysql_query("select * from `forum` where type='f' and id='" . $razd['refid'] . "';");
            $fr = mysql_fetch_array($q4);
            $mid = $razd['refid'];
            $pfr = $fr['text'];
            break;
        case "r" :
            $mid = $ms['refid'];
            $q3 = mysql_query("select * from `forum` where type='f' and id='" . $ms['refid'] . "';");
            $fr = mysql_fetch_array($q3);
            $pfr = $fr['text'];
            break;
        case "f" :
            $mid = $id;
            $pfr = $ms['text'];
            break;
        default :
            echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
            break;
    }
    $mod = mysql_query("select * from `forum` where type='a' and refid='" . $mid . "';");
    $mod2 = mysql_num_rows($mod);
    echo "Модеры подфорума <font color='" . $cntem . "'>$pfr</font><br/><br/>";
    if ($mod2 != 0) {
        while ($mod1 = mysql_fetch_array($mod)) {
            $uz = mysql_query("select * from `users` where name='" . $mod1['from'] . "';");
            $uz1 = mysql_fetch_array($uz);
            if ($uz1['rights'] == 3) {
                if ((!empty ($_SESSION['uid'])) && ($login != $mod1['from'])) {
                    echo "<a href='../str/anketa.php?id=" . $uz1['id'] . "'>$mod1[from]</a>";
                }
                else {
                    echo $mod1['from'];
                }
                $ontime = $uz1['lastdate'];
                $ontime2 = $ontime + 300;
                if ($realtime > $ontime2) {
                    echo '<span class="red"> [Off]</span><br/>';
                }
                else {
                    echo '<span class="green"> [ON]</span><br/>';
                }
            }
        }
        echo '<hr/>';
    }
    else {
        echo 'Не назначены<br/>';
    }
}
echo "<a href='index.php?id=" . $id . "'>Назад</a><br/>";

?>