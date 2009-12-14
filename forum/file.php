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

$error = false;
if ($id) {
    $req = mysql_query("SELECT * FROM `cms_forum_files` WHERE `id` = '" . $id . "' LIMIT 1");
    if (mysql_num_rows($req) > 0) {
        $res = mysql_fetch_array($req);
        if (file_exists('./files/' . $res['filename'])) {
            $dlcount = $res['dlcount'] + 1;
            mysql_query("UPDATE `cms_forum_files` SET  `dlcount` = '" . $dlcount . "' WHERE `id` = '" . $id . "'");
            header('location: ./files/' . $res['filename']);
        }
        else {
            $error = 'Файла не существует';
        }
    }
    else {
        $error = 'Файла не существует';
    }
    if ($error) {
        require_once ("../incfiles/head.php");
        echo '<p><b>ОШИБКА!</b><br/>' . $error . '<a href="index.php">В форум</a></p>';
        require_once ("../incfiles/end.php");
        exit;
    }
}
else {
    header('location: index.php');
}

?>