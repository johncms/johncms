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
// Рекламный модуль от FlySelf
*/

define('_IN_JOHNCMS', 1);

require_once ("../incfiles/core.php");

$req = mysql_query("SELECT * FROM `cms_ads` WHERE `id` = '$id' LIMIT 1");
if (mysql_num_rows($req)) {
    $res = mysql_fetch_assoc($req);
    $count_link = $res['count'] + 1;
    mysql_query("UPDATE `cms_ads` SET `count` = '" . $count_link . "'  WHERE `id` = '$id'");
    header('Location: ' . $res['link']);
}
else {
    header("Location: http://gazenwagen.com/index.php?act=404");
}

?>
