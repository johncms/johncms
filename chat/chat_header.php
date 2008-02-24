<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC2                                                        //
// Дата релиза: 08.02.2008                                                    //
// Авторский сайт: http://gazenwagen.com                                      //
////////////////////////////////////////////////////////////////////////////////
// Оригинальная идея и код: Евгений Рябинин aka JOHN77                        //
// E-mail: john773@yandex.ru                                                  //
// Модификация, оптимизация и дизайн: Олег Касьянов aka AlkatraZ              //
// E-mail: alkatraz@batumi.biz                                                //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
// Внимание!                                                                  //
// Авторские версии данных скриптов публикуются ИСКЛЮЧИТЕЛЬНО на сайте        //
// http://gazenwagen.com                                                      //
// На этом же сайте оказывается техническая поддержка                         //
// Если Вы скачали данный скрипт с другого сайта, то его работа не            //
// гарантируется и поддержка не оказывается.                                  //
////////////////////////////////////////////////////////////////////////////////
*/

if ($gzip == "1")
{
    ob_start('ob_gzhandler');
}
$agent = $_SERVER['HTTP_USER_AGENT'];
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
if (eregi("msie", $agent) && eregi("windows", $agent))
{
    header('Content-type: text/html; charset=UTF-8');
} else
{
    header('Content-type: application/xhtml+xml; charset=UTF-8');
}
echo '<?xml version="1.0" encoding="utf-8"?>';
echo "\n" . '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" ';
echo "\n" . '"http://www.wapforum.org/DTD/xhtml-mobile10.dtd">';
echo "\n" . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">';
echo "\n" . '<head>';
echo "\n" . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
if ($arefresh)
    echo "\n" . '<meta http-equiv="refresh" content="' . $obn . ';URL=index.php?id=' . intval($_GET['id']) . '&amp;refr=' . $refr . '"/>';
echo "\n" . '<link rel="shortcut icon" href="favicon . ico" />';
echo "\n" . '<title>' . $textl . '</title>';

////////////////////////////////////////////////////////////
// Стиль для комнат чата. Если надо, то меняйте...        //
////////////////////////////////////////////////////////////
echo "\n" . '<link rel="stylesheet" href="' . $home . '/chat/style.css" type="text/css" />' . "\n";
echo '</head><body><div>';

?>