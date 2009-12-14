<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                                                                    //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@johncms.com                     //
// Олег Касьянов aka AlkatraZ          alkatraz@johncms.com                   //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

echo '<div class="phdr"><b>Новые статьи</b></div>';
$old = $realtime - (3 * 24 * 3600);
$req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `time` > '" . $old . "' AND `type` = 'bk' AND `moder` = '1'");
$total = mysql_result($req, 0);
if ($total > 0) {
    $req = mysql_query("SELECT * FROM `lib` WHERE `time` > '" . $old . "' AND `type` = 'bk' AND `moder` = '1' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
    while ($newf = mysql_fetch_array($req)) {
        echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
        $vr = $newf['time'] + $set_user['sdvig'] * 3600;
        $vr = date("d.m.y / H:i", $vr);
        echo $div;
        echo '<b><a href="?id=' . $newf['id'] . '">' . htmlentities($newf['name'], ENT_QUOTES, 'UTF-8') . '</a></b><br/>';
        echo htmlentities($newf['announce'], ENT_QUOTES, 'UTF-8') . '<br />';
        echo 'Добавил: ' . $newf['avtor'] . ' (' . $vr . ')<br/>';
        $nadir = $newf['refid'];
        $dirlink = $nadir;
        $pat = "";
        while ($nadir != "0") {
            $dnew = mysql_query("select * from `lib` where type = 'cat' and id = '" . $nadir . "';");
            $dnew1 = mysql_fetch_array($dnew);
            $pat = $dnew1['text'] . '/' . $pat;
            $nadir = $dnew1['refid'];
        }
        $l = mb_strlen($pat);
        $pat1 = mb_substr($pat, 0, $l - 1);
        echo '[<a href="index.php?id=' . $dirlink . '">' . $pat1 . '</a>]</div>';
        ++$i;
    }
    echo '<div class="phdr">Всего: ' . $total . '</div>';
    // Навигация по страницам
    if ($total > $kmess) {
        echo '<p>' . pagenav('index.php?act=new&amp;', $start, $total, $kmess) . '</p>';
        echo '<p><form action="index.php" method="get"><input type="hidden" name="act" value="new"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
    }
}
else {
    echo '<p>За три дня новых статей не было</p>';
}
echo '<p><a href="index.php">В библиотеку</a></p>';

?>