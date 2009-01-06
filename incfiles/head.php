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

defined('_IN_JOHNCMS') or die('Error:restricted access');

if (!isset($headmod))
    $headmod = '';
if ($headmod == "mainpage")
{
    $textl = $copyright;
}

if ($headmod != "auto")
{
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
    header((stristr($agn, "msie") && stristr($agn, "windows")) ? 'Content-type: text/html; charset=UTF-8' : 'Content-type: application/xhtml+xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    echo "\n" . '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile10.dtd">';
    echo "\n" . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">';
    echo "\n" . '<head><meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8"/>';
    echo "\n" . '<link rel="shortcut icon" href="' . $home . '/favicon.ico" />';
    echo "\n" . '<meta name="copyright" content="Powered by JohnCMS" />'; // ВНИМАНИЕ!!! Данный копирайт удалять нельзя
    echo "\n" . '<link rel="alternate" type="application/rss+xml" title="RSS | Новости ресурса" href="' . $home . '/rss/rss.php" />';
    echo "\n" . '<title>' . $textl . '</title>';
    if ($skin == "")
        $skin = $skindef;
    echo "\n" . '<link rel="stylesheet" href="' . $home . '/theme/' . $skin . '/style.css" type="text/css" />';
    echo "\n" . '</head><body>';

    // Выводим логотип
	echo '<div><img src="' . $home . '/theme/' . $skin . '/images/logo.gif" alt=""/></div>';

    ////////////////////////////////////////////////////////////
    // Выводим верхний блок с приветствием                    //
    ////////////////////////////////////////////////////////////
    echo '<div class="header">Привет ' . ($user_id ? '<b> ' . $login . '</b>!' : 'прохожий!') . '</div>';

    ////////////////////////////////////////////////////////////
    // Выводим меню пользователя                              //
    ////////////////////////////////////////////////////////////
    echo '<div class="tmn">';
    echo ($headmod != "mainpage" || isset($_GET['do']) || isset($_GET['mod'])) ? '<a href=\'' . $home . '\'>На главную</a> | ' : '';
    echo ($user_id && $_GET['mod'] != 'cab') ? '<a href="' . $home . '/index.php?mod=cab">Личное</a> | ' : '';
    echo $user_id ? '<a href="' . $home . '/exit.php">Выход</a>' : '<a href="' . $home . '/in.php">Вход</a> | <a href="' . $home . '/registration.php">Регистрация</a>';
    echo '</div>';

    ////////////////////////////////////////////////////////////
    // Служебные функции пользователя                         //
    ////////////////////////////////////////////////////////////
    echo '<div class="maintxt">';
    require_once ('usersystem.php');
}

?>
