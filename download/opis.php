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
    if ($_GET['file'] == "") {
        echo $lng_dl['file_not_selected'] . "<br/><a href='?'>" . $lng['back'] . "</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $file = intval($_GET['file']);
    $file1 = mysql_query("SELECT * FROM `download` WHERE `type` = 'file' AND `id` = '" . $file . "';");
    $file2 = mysql_num_rows($file1);
    $adrfile = mysql_fetch_array($file1);
    if (($file1 == 0) || (!is_file("$adrfile[adres]/$adrfile[name]"))) {
        echo $lng_dl['file_not_selected'] . "<br/><a href='?'>" . $lng['back'] . "</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $stt = "$adrfile[text]";
    if (isset ($_POST['submit'])) {
        $newt = functions::check($_POST['newt']);
        mysql_query("update `download` set `text`='" . $newt . "' where `id`='" . $file . "';");
        echo $lng_dl['description_changed'] . "<br/>";
    }
    else {
        $str = str_replace("<br/>", "\r\n", $adrfile['text']);
        echo "<form action='?act=opis&amp;file=" . $file . "' method='post'>";
        echo $lng['description'] . ':<br/><textarea rows="4" name="newt">' . $str . '</textarea><br/>';
        echo "<input type='submit' name='submit' value='Изменить'/></form><br/>";
    }
}
else {
    echo "Нет доступа!";
}
echo "<p><a href='?act=view&amp;file=" . $file . "'>" . $lng['back'] . "</a></p>";

?>