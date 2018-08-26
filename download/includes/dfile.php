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
if ($rights == 4 || $rights >= 6) {
    if (!$file) {
        echo $lng_dl['file_not_selected'] . "<br/><a href='?'>" . $lng['back'] . "</a><br/>";
        require_once('../incfiles/end.php');
        exit;
    }
    $error = true;
    $stmt = $db->query("select * from `download` where type = 'file' and id = '" . $file . "' LIMIT 1;");
    if ($stmt->rowCount()) {
        $adrfile = $stmt->fetch();
        if (is_file($adrfile['adres'] . '/' . $adrfile['name'])) {
            $error = false;
        }
    }
    if ($error) {
        echo $lng_dl['file_not_selected'] . "<br/><a href='?'>" . $lng['back'] . "</a><br/>";
        require_once('../incfiles/end.php');
        exit;
    }
    $refd1 = $db->query("select * from `download` where type = 'cat' and `id` = '" . $adrfile['refid'] . "' LIMIT 1")->fetch();
    if (isset($_POST['submit'])) {
        unlink($adrfile['adres'] . '/' . $adrfile['name']);
        $db->exec("delete from `download` where `id` = '" . $adrfile['id'] . "' LIMIT 1;");
        echo '<p>' . $lng_dl['file_deleted'] . '</p>';
    } else {
        echo '<p>' . $lng['delete_confirmation'] . '</p>' .
            '<form action="index.php?act=dfile&amp;file=' . $file . '" method="post">' .
            '<input type="submit" name="submit" value="' . $lng['delete'] . '" />' .
            '</form><p><a href="index.php?act=view&amp;file=' . $file . '">' . $lng['cancel'] . '</a></p>';
    }
}
echo "<p><a href='?cat=" . $refd1['id'] . "'>" . $lng['back'] . "</a></p>";
