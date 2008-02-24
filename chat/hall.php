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

require_once ("../incfiles/head.php");
require_once ("../incfiles/inc.php");
$_SESSION['intim'] = '';
$q = mysql_query("select * from `chat` where type='r' order by realid ;");
while ($mass = mysql_fetch_array($q))
{
    echo '<div class="menu"><img alt="" src="../images/arrow.gif" width="7" height="12" />&nbsp;&nbsp;';
    echo '<a href="index.php?id=' . $mass[id] . '"><font color="' . $cntem . '">' . $mass[text] . '</font></a> (' . wch($mass[id]) . ')';
    echo '</div>';
}
echo '<hr/>';
echo '<a href="who.php">Кто в чате(' . wch() . ')</a><br/>';
echo '<a href="index.php?act=moders&amp;id=' . $id . '">Модераторы</a><br/>';
echo "<a href='../str/usset.php?act=chat'>Настройки чата</a><br/>";
require_once ('../incfiles/end.php');

?>