<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights == 5 || $rights >= 6) {
    echo '<div class="phdr">' . $lng_lib['articles_moderation'] . '</div>';
    if ($id && (isset($_GET['yes']))) {
        mysql_query("UPDATE `lib` SET `moder` = '1' , `time` = '$realtime' WHERE `id` = '$id'");
        $req = mysql_query("SELECT `name` FROM `lib` WHERE `id` = '$id'");
        $res = mysql_fetch_array($req);
        echo '<div class="rmenu">' . $lng_lib['article'] . ' <b>' . $res['name'] . '</b> ' . $lng_lib['added_to_database'] . '</div>';
    }
    if (isset($_GET['all'])) {
        $req = mysql_query("SELECT `id` FROM `lib` WHERE `type` = 'bk' AND `moder` = '0'");
        while ($res = mysql_fetch_array($req)) {
            mysql_query("UPDATE `lib` SET `moder` = '1', `time` = '$realtime' WHERE `id` = '" . $res['id'] . "'");
        }
        echo '<p>' . $lng_lib['added_all'] . '</p>';
    }
    $req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '0'");
    $total = mysql_result($req, 0);
    if ($total > 0) {
        $req = mysql_query("SELECT * FROM `lib` WHERE `type` = 'bk' AND `moder` = '0' LIMIT " . $start . "," . $kmess);
        while ($res = mysql_fetch_array($req)) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            $vr = $res['time'] + $set_user['sdvig'] * 3600;
            $vr = date("d.m.y / H:i", $vr);
            $tx = $res['soft'];
            echo "<a href='index.php?id=" . $res['id'] . "'>$res[name]</a><br/>" . $lng_lib['added'] . ": $res[avtor] ($vr)<br/>$tx <br/>";
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
            echo "[$pat1]<br/><a href='index.php?act=moder&amp;id=" . $res['id'] . "&amp;yes'> " . $lng_lib['approve'] . "</a></div>";
            ++$i;
        }
        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<p>' . functions::display_pagination('index.php?act=moder&amp;', $start, $total, $kmess) . '</p>';
            echo '<p><form action="index.php" method="get"><input type="hidden" value="moder" name="act" /><input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
        }
        echo '<p><a href="index.php?act=moder&amp;all">' . $lng_lib['approve_all'] . '</a><br />';
    } else {
        echo '<p>';
    }
}
echo '<a href="?">' . $lng_lib['to_library'] . '</a></p>';

?>