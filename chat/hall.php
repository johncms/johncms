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

require_once ("../incfiles/head.php");
$_SESSION['intim'] = '';
$q = mysql_query("select * from `chat` where type='r' order by realid ;");
while ($mass = mysql_fetch_array($q))
{
    echo '<div class="menu"><img alt="" src="../images/arrow.gif" width="7" height="12" />&nbsp;';
    echo '<a href="index.php?id=' . $mass[id] . '"><font color="' . $cntem . '">' . $mass[text] . '</font></a> (' . wch($mass[id]) . ')';
    echo '</div>';
}
echo '<hr/>';
echo '<p><a href="who.php">Кто в чате(' . wch() . ')</a><br/>';
echo '<a href="index.php?act=moders&amp;id=' . $id . '">Модераторы</a><br/>';
echo "<a href='../str/usset.php?act=chat'>Настройки чата</a></p>";
require_once ('../incfiles/end.php');

?>