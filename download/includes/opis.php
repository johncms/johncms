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

require_once ("../incfiles/head.php");
if ($rights == 4 || $rights >= 6) {
    $file = isset($_GET['file']) ? abs(intval($_GET['file'])) : 0;
    if (!$file) {
        echo $lng_dl['file_not_selected'] . "<br/><a href='?'>" . $lng['back'] . "</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $file1 = $db->query("SELECT * FROM `download` WHERE `type` = 'file' AND `id` = '" . $file . "' LIMIT 1;");
    if (!$file1->rowCount()) {
        echo $lng_dl['file_not_selected'] . "<br/><a href='?'>" . $lng['back'] . "</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $adrfile = $file1->fetch();
    if (!is_file($adrfile['adres'] . '/' . $adrfile['name'])) {
        echo $lng_dl['file_not_selected'] . "<br/><a href='?'>" . $lng['back'] . "</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    if (isset($_POST['submit'])) {
        $newt = functions::checkin($_POST['newt']);
        $stmt = $db->prepare("update `download` set `text`= ? where `id`='" . $file . "' LIMIT 1;");
        $stmt->execute([
            $newt
        ]);
        echo $lng_dl['description_changed'] . "<br/>";
    } else {
        echo "<form action='?act=opis&amp;file=" . $file . "' method='post'>";
        echo $lng['description'] . ':<br/><textarea rows="4" name="newt">' . _e($adrfile['text']) . '</textarea><br/>';
        echo "<input type='submit' name='submit' value='Изменить'/></form><br/>";
    }
}
else {
    echo "Нет доступа!";
}
echo "<p><a href='?act=view&amp;file=" . $file . "'>" . $lng['back'] . "</a></p>";
