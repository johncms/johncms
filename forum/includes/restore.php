<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

if (($rights != 3 && $rights < 6) || !$id) {
    header('Location: ' . $homeurl . '/?err'); exit;
}
$stmt = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 't' LIMIT 1");
if ($stmt->rowCount() {
    $res = $stmt->fetch();
    $db->exec("UPDATE `forum` SET `close` = '0', `close_who` = '$login' WHERE `id` = '$id'");
    header('Location: index.php?id=' . $id); exit;
} else {
    header('Location: index.php'); exit;
}
