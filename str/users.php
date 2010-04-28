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

define('_IN_JOHNCMS', 1);

$headmod = 'users';
$textl = 'Юзеры';
require_once("../incfiles/core.php");
require_once("../incfiles/head.php");

echo '<div class="phdr"><b>Список пользователей</b></div>';
$req = mysql_query("SELECT COUNT(*) FROM `users`");
$total = mysql_result($req, 0);
$req = mysql_query("SELECT `id`, `name`, `sex`, `lastdate`, `datereg`, `status`, `rights`, `ip`, `browser`, `rights` FROM `users` WHERE `preg` = 1 ORDER BY `datereg` DESC LIMIT $start, $kmess");
while ($res = mysql_fetch_assoc($req)) {
    echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
    echo show_user($res, 1, (($rights > 1) ? 2 : 0)) . '</div>';
    ++$i;
}
echo '<div class="phdr">Всего: ' . $total . '</div><p>';
if ($total > $kmess) {
    echo '<p>' . pagenav('users.php?', $start, $total, $kmess) . '</p>';
    echo '<p><form action="users.php" method="post"><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
}
echo '<a href="users_search.php">Поиск пользователя</a><br /><a href="' . $_SESSION['refsm'] . '">Назад</a></p>';

require_once("../incfiles/end.php");

?>