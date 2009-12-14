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

if ($rights == 3 || $rights >= 6) {
    if (empty ($_GET['id'])) {
        require_once ("../incfiles/head.php");
        echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    $req = mysql_query("SELECT COUNT(*) FROM `forum` WHERE `id` = '" . $id . "' AND `type` = 't'");
    if (mysql_result($req, 0) > 0) {
        mysql_query("UPDATE `forum` SET  `vip` = '" . (isset ($_GET['vip']) ? '1' : '0') . "' WHERE `id` = '" . $id . "'");
        header('Location: index.php?id=' . $id);
    }
    else {
        require_once ("../incfiles/head.php");
        echo '<p>ОШИБКА!<br/><a href="index.php">Назад</a></p>';
        require_once ("../incfiles/end.php");
        exit;
    }
}

?>