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
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");
echo '<div class="phdr">Список пользователей</div>';
$req = mysql_query("SELECT COUNT(*) FROM `users`");
$total = mysql_result($req, 0); // Общее число зареганных юзеров
$req = mysql_query("SELECT `id`, `name`, `sex`, `lastdate`, `datereg` FROM `users` ORDER BY `datereg` DESC LIMIT " . $start . "," . $kmess);
while ($arr = mysql_fetch_array($req))
{
    echo ceil(ceil($i / 2) - ($i / 2)) == 0 ? '<div class="list1">' : '<div class="list2">';
    echo $arr['datereg'] > $realtime - 86400 ? '<img src="../images/add.gif" alt=""/>&nbsp;' : '';
    if ($arr['sex'] == "m")
    {
        echo '<img src="../images/m.gif" alt=""/>&nbsp;';
    } elseif ($arr['sex'] == "zh")
    {
        echo '<img src="../images/f.gif" alt=""/>&nbsp;';
    }
    if (empty($_SESSION['uid']) || $_SESSION['uid'] == $arr['id'])
    {
        print '<b>' . $arr['name'] . '</b>';
    } else
    {
        print "<a href='anketa.php?user=" . $arr['id'] . "'>$arr[name]</a>";
    }
    switch ($arr['rights'])
    {
        case 7:
            echo ' Adm ';
            break;
        case 6:
            echo ' Smd ';
            break;
        case 5:
            echo ' Mod ';
            break;
        case 4:
            echo ' Mod ';
            break;
        case 3:
            echo ' Mod ';
            break;
        case 2:
            echo ' Mod ';
            break;
        case 1:
            echo ' Kil ';
            break;
    }
    $ontime = $arr['lastdate'];
    $ontime2 = $ontime + 300;
    if ($realtime > $ontime2)
    {
        echo '<font color="#FF0000"> [Off]</font><br/>';
    } else
    {
        echo '<font color="#00AA00"> [ON]</font><br/>';
    }
    echo '</div>';
    ++$i;
}
echo '<div class="bmenu">Всего: ' . $total . '</div><p>';
if ($total > $kmess)
{
    echo '<p>' . pagenav('users.php?', $start, $total, $kmess) . '</p>';
    echo '<p><form action="users.php" method="get"><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
}
echo '<a href="' . $_SESSION['refsm'] . '">Назад</a></p>';

require_once ("../incfiles/end.php");

?>