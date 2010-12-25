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
if (!empty($_GET['cat'])) {
    $cat = $_GET['cat'];
    provcat($cat);
    if ($rights == 4 || $rights >= 6) {
        echo "<form action='?act=upl' method='post' enctype='multipart/form-data'>
         <p>" . $lng['select'] . " (max " . $set['flsz'] . " кб.):<br/>
         <input type='file' name='fail'/></p>
         <p>" . $lng_dl['screenshot'] . ":<br/>
         <input type='file' name='screens'/></p>
         <p>" . $lng['description'] . ":<br/>
         <textarea name='opis'></textarea></p>
         <p>" . $lng_dl['save_as'] . ":<br/>
         <input type='text' name='newname'/></p>
         <input type='hidden' name='cat' value='" . $cat . "'/>
         <p><input type='submit' value='" . $lng_dl['upload'] . "'/></p>
         </form>";
    } else {
        echo "Нет доступа!<br/>";
    }
    echo "<a href='?cat=" . $cat . "'>" . $lng['back'] . "</a><br/>";
} else {
    echo 'ERROR';
}
?>