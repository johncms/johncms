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

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header((stristr($agn, "msie") && stristr($agn, "windows")) ? 'Content-type: text/html; charset=UTF-8' : 'Content-type: application/xhtml+xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="utf-8"?>';
echo "\n" . '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" ';
echo "\n" . '"http://www.wapforum.org/DTD/xhtml-mobile10.dtd">';
echo "\n" . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">';
echo "\n" . '<head>';
echo "\n" . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
if ($arefresh)
    echo "\n" . '<meta http-equiv="refresh" content="' . $set_chat['refresh'] . ';URL=index.php?id=' . intval($_GET['id']) . '&amp;refr=' . $refr . '"/>';
echo "\n" . '<link rel="shortcut icon" href="favicon . ico" />';
echo "\n" . '<title>' . $textl . '</title>';

////////////////////////////////////////////////////////////
// Стиль для комнат чата. Если надо, то меняйте...        //
////////////////////////////////////////////////////////////
echo "\n" . '<link rel="stylesheet" href="' . $home . '/chat/style.css" type="text/css" />' . "\n";
echo '</head><body><div>';
$newl = mysql_query("select * from `privat` where user = '" . $login . "' and type = 'in' and chit = 'no';");
$countnew = mysql_num_rows($newl);
if ($countnew > 0) {
    echo "<div><a href='$home/str/pradd.php?act=in&amp;new'><b><font color='red'>Вам письмо: $countnew</font></b></a></div>";
}

?>