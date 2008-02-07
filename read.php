<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC1                                                        //
// Дата релиза: 08.02.2008                                                    //
// Авторский сайт: http://gazenwagen.com                                      //
////////////////////////////////////////////////////////////////////////////////
// Оригинальная идея и код: Евгений Рябинин aka JOHN77                        //
// E-mail: 
// Модификация, оптимизация и дизайн: Олег Касьянов aka AlkatraZ              //
// E-mail: alkatraz@batumi.biz                                                //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
// Внимание!                                                                  //
// Авторские версии данных скриптов публикуются ИСКЛЮЧИТЕЛЬНО на сайте        //
// http://gazenwagen.com                                                      //
// Если Вы скачали данный скрипт с другого сайта, то его работа не            //
// гарантируется и поддержка не оказывается.                                  //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_PUSTO', 1);
session_name("SESID");
session_start();
$headmod = 'info';
$textl = 'Информация';
require ("incfiles/db.php");
require ("incfiles/func.php");
require ("incfiles/data.php");
require ("incfiles/head.php");
require ("incfiles/inc.php");
if (empty($_GET['f']))
{
    echo "<a href='read.php?f=pages/actmail'>Активация e-mail</a><br/>";
    echo "<a href='read.php?f=pages/forum'>Правила форума</a><br/>";
    echo "<a href='read.php?f=pages/forumfaq'>FAQ по форуму</a><br/>";
    echo "<a href='read.php?f=pages/trans'>Справка по транслиту</a><br/>";
    require ("incfiles/end.php");
    exit;
}
if (stristr($_GET['f'], "../"))
{
    echo "<b>Гыы шо,типо кулхацкер ниибаццо?</b><br/>";
    require ("incfiles/end.php");
    exit;
}
if (!eregi("[^a-z0-9_/-]", $_GET['f']))
{
    include "$_GET[f].$ras_pages";
    echo "<a href='read.php'>В FAQ</a><br/>";
}
require ("incfiles/end.php");

?>