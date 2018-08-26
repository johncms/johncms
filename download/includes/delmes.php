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

if ($rights == 4 || $rights >= 6) {
    if (!$id) {
        require_once ("../incfiles/head.php");
        echo "ERROR<br/><a href='index.php?'>Back</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $stmt = $db->query('SELECT `refid` FROM `download` WHERE `id` = "' . $id . '" AND `type` = "komm" LIMIT 1;');
    if ($stmt->rowCount()) {
        $ms = $stmt->fetch();
        $db->exec('DELETE FROM `download` WHERE `id` = "' . $id . '" LIMIT 1');
        header('Location: index.php?act=komm&id=' . $ms['refid']); exit;
    } else {
        require_once ("../incfiles/head.php");
        echo "ERROR<br/><a href='index.php?'>Back</a><br/>";
    }
}
