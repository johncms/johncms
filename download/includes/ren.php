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
require_once("../incfiles/head.php");
if (($rights == 4 || $rights >= 6) && $cat) {
    $error = false;
    $stmt = $db->query('SELECT * FROM `download` WHERE `type` = "cat" AND `id` = "' . $cat. '" LIMIT 1');
    if ($stmt->rowCount()) {
        $res = $stmt->fetch();
        if (!is_dir($res['adres'] . '/' . $res['name'])) {
            $error = true;
        }
    } else {
        $error = true;
    }
    if (!$error) {
        if (isset($_POST['submit'])) {
            if (!empty($_POST['newrus'])) {
                $newrus = functions::checkin($_POST['newrus']);
            } else {
                $newrus = $res['text'];
            }
            $stmt = $db->prepare("update `download` set `text`= ? where id='" . $cat . "' LIMIT 1;");
            $stmt->execute([
                $newrus
            ]);
            echo '<p>' . $lng_dl['name_changed'] . '</p>';
        } else {
            echo "<form action='?act=ren&amp;cat=" . $cat . "' method='post'><p>";
            echo $lng_dl['folder_name_for_list'] . "<br/><input type='text' name='newrus' value='" . _e($res['text']) . "'/></p>";
            echo "<p><input type='submit' name='submit' value='" . $lng_dl['change'] . "'/></p></form>";
        }
        echo "<p><a href='?cat=" . $cat . "'>" . $lng['back'] . "</a></p>";
    } else {
        echo 'ERROR<br/><a href="?">Back</a><br/>';
    }
} else {
    echo "<p><a href='index.php'>" . $lng['back'] . "</a></p>"
}
