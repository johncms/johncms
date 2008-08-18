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

// Выводим сообщение Админу при закрытом чате
if (!$set['mod_chat'])
    echo '<p><font color="#FF0000"><b>Чат закрыт!</b></font></p>';

echo '<div class="phdr">Чат</div>';
$_SESSION['intim'] = '';
$q = mysql_query("select * from `chat` where type='r' order by realid ;");
while ($mass = mysql_fetch_array($q))
{
    echo '<div class="menu"><a href="index.php?id=' . $mass['id'] . '">' . $mass['text'] . '</a> (' . wch($mass['id']) . ')</div>';
}
echo '<div class="bmenu"><a href="who.php">Кто в чате(' . wch() . ')</a></div>';
echo '<p><a href="index.php?act=moders&amp;id=' . $id . '">Модераторы</a><br/>';
echo "<a href='../str/usset.php?act=chat'>Настройки чата</a></p>";
require_once ('../incfiles/end.php');

?>