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

echo '<div class="phdr"><b>Чат</b></div>';
$_SESSION['intim'] = '';
$q = mysql_query("select * from `chat` where type='r' order by realid ;");
while ($mass = mysql_fetch_array($q)) {
    echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
    echo '<a href="index.php?id=' . $mass['id'] . '">' . $mass['text'] . '</a> (' . wch($mass['id']) . ')</div>';
    ++$i;
}
echo '<div class="bmenu">В прихожей (' . wch() . ')</div>';
echo '<p><a href="who.php">Кто в чате? (' . wch(0, 1) . ')</a><br/>';
echo '<a href="index.php?act=moders&amp;id=' . $id . '">Модераторы</a><br/>';
echo "<a href='../str/usset.php?act=chat'>Настройки чата</a></p>";
require_once ('../incfiles/end.php');

?>