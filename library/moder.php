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

if ($rights == 5 || $rights >= 6) {
    echo '<div class="phdr">Модерация статей</div>';
    if ($id && (isset ($_GET['yes']))) {
        mysql_query("UPDATE `lib` SET `moder` = '1' , `time` = '" . $realtime . "' WHERE `id` = '" . $id . "'");
        $req = mysql_query("SELECT `name` FROM `lib` WHERE `id` = '" . $id . "'");
        $res = mysql_fetch_array($req);
        echo '<div class="rmenu">Статья <b>' . $res['name'] . '</b> добавлена в базу</div>';
    }
    if (isset ($_GET['all'])) {
        $req = mysql_query("SELECT `id` FROM `lib` WHERE `type` = 'bk' AND `moder` = '0'");
        while ($res = mysql_fetch_array($req)) {
            mysql_query("UPDATE `lib` SET `moder` = '1', `time` = '" . $realtime . "' WHERE `id` = '" . $res['id'] . "'");
        }
        echo '<p>Все файлы добавлены в базу</p>';
    }
    $req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '0'");
    $total = mysql_result($req, 0);
    if ($total > 0) {
        $req = mysql_query("SELECT * FROM `lib` WHERE `type` = 'bk' AND `moder` = '0' LIMIT " . $start . "," . $kmess);
        while ($res = mysql_fetch_array($req)) {
            echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
            $vr = $res['time'] + $set_user['sdvig'] * 3600;
            $vr = date("d.m.y / H:i", $vr);
            $tx = $res['soft'];
            echo "<a href='index.php?id=" . $res['id'] . "'>$res[name]</a><br/>Добавил: $res[avtor] ($vr)<br/>$tx <br/>";
            $nadir = $res['refid'];
            $pat = "";
            while ($nadir != "0") {
                $dnew = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $nadir . "';");
                $dnew1 = mysql_fetch_array($dnew);
                $pat = "$dnew1[text]/$pat";
                $nadir = $dnew1['refid'];
            }
            $l = mb_strlen($pat);
            $pat1 = mb_substr($pat, 0, $l - 1);
            echo "[$pat1]<br/><a href='index.php?act=moder&amp;id=" . $res['id'] . "&amp;yes'> Принять</a></div>";
            ++$i;
        }
        echo '<div class="phdr">Всего: ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<p>' . pagenav('index.php?act=moder&amp;', $start, $total, $kmess) . '</p>';
            echo '<p><form action="index.php" method="get"><input type="hidden" value="moder" name="act" /><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
        }
        echo '<p><a href="index.php?act=moder&amp;all">Принять все!</a><br />';
    }
    else {
        echo '<p>';
    }
}
else {
    echo "Нет доступа!<br/>";
}
echo '<a href="?">В библиотеку</a></p>';

?>