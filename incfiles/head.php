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

if ($headmod != "auto")
{
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
    if (stristr($agn, "msie") && stristr($agn, "windows"))
    {
        header('Content-type: text/html; charset=UTF-8');
    } else
    {
        header('Content-type: application/xhtml+xml; charset=UTF-8');
    }
    echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    echo "\n" . '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" ';
    echo "\n" . '"http://www.wapforum.org/DTD/xhtml-mobile10.dtd">';
    echo "\n" . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">';
    echo "\n" . '<head><meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8"/>';
    echo "\n" . '<link rel="shortcut icon" href="favicon.ico" />';
    echo "\n" . '<link rel="alternate" type="application/rss+xml" title="RSS | Новости ресурса" href="' . $home . '/rss/rss.php" />';
    echo "\n" . '<title>' . $textl . '</title>';
    echo "\n" . '<link rel="stylesheet" href="' . $home . '/style.css" type="text/css" />';
    echo "\n" . '</head><body>';
    // Внимание!!! Данный копирайт удалять нельзя.
    echo "\n" . '<!-- Powered by JohnCMS -->' . "\n";

    // Выводим логотип. Если нужно, то раскомментируйте строку ниже
    //echo '<div><center><img src="' . $home . '/images/logo.gif" alt=""/></center></div>';

    // Выводим название сайта
    echo '<div class="header">' . $textl . '</div>';

    // Выводим меню пользователя
    echo '<div class="topmenu">';

    // Выводим приветствие
    //if (!empty($_SESSION['uid']))
    //{
    //    echo "Привет,<b> " . $login . "</b>!<br/>";
    //} else
    //{
    //    echo "Привет, прохожий!<br/>";
    //}

    // Выводим меню пользователя вверху сайта
    if ($headmod != "mainpage" || isset($_GET['do']))
    {
        echo '<a href=\'' . $home . '\'>На главную</a> | ';
    }
    if ($user_id)
    {
        echo "<a href='" . $home . "/index.php?do=cab'>Личное</a> | <a href='" . $home . "/exit.php'>Выход</a><br/>";
    } else
    {
        echo "<a href='" . $home . "/in.php'>Вход</a> | <a href='" . $home . "/registration.php'>Регистрация</a><br/>";
    }
    echo '</div>';
    require_once ('usersystem.php');
    echo '<div class="maintxt">';
}

?>