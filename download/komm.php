<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

require_once("../incfiles/head.php");

$fayl = mysql_query("SELECT * FROM `download` WHERE type='file' AND id='" . $id . "'");
if (!mysql_num_rows($fayl)) {
    echo "ERROR<br/><a href='?'>Back</a><br/>";
    require_once('../incfiles/end.php');
    exit;
}

if (!$set['mod_down_comm'] && $rights < 7) {
    echo '<p>ERROR<br/><a href="index.php">Back</a></p>';
    require_once('../incfiles/end.php');
    exit;
}
$mess = mysql_query("SELECT * FROM `download` WHERE type='komm' AND refid='" . $id . "' ORDER BY time DESC ;");
$countm = mysql_num_rows($mess);

$fayl1 = mysql_fetch_array($fayl);
echo '<p>' . $lng['comments'] . ": <span class='red'>$fayl1[name]</span></p>";
if ($user_id && !$ban['1'] && !$ban['10']) {
    echo "<a href='?act=addkomm&amp;id=" . $id . "'>Написать</a><br/>";
}
if (empty ($_GET['page'])) {
    $page = 1;
} else {
    $page = intval($_GET['page']);
}
$start = $page * $kmess - $kmess;
if ($countm < $start + $kmess) {
    $end = $countm;
} else {
    $end = $start + $kmess;
}

while ($mass = mysql_fetch_array($mess)) {
    if ($i >= $start && $i < $end) {
        $d = $i / 2;
        $d1 = ceil($d);
        $d2 = $d1 - $d;
        $d3 = ceil($d2);
        if ($d3 == 0) {
            $div = "<div class='list2'>";
        } else {
            $div = "<div class='list1'>";
        }
        $uz = @ mysql_query("SELECT * FROM `users` WHERE name='" . functions::check($mass[avtor]) . "';");
        $mass1 = @ mysql_fetch_array($uz);
        echo "$div";
        if ((!empty ($_SESSION['uid'])) && ($_SESSION['uid'] != $mass1[id])) {
            echo "<a href='../users/profile.php?user=" . $mass1[id] . "'>$mass[avtor]</a>";
        } else {
            echo "$mass[avtor]";
        }
        switch ($mass1[rights]) {
            case 7 :
                echo ' Adm ';
                break;
            case 6 :
                echo ' Smd ';
                break;
            case 4 :
                echo ' Mod ';
                break;
            case 1 :
                echo ' Kil ';
                break;
        }
        $ontime = $mass1[lastdate];
        $ontime2 = $ontime + 300;
        if (time() > $ontime2) {
            echo " [Off]";
        } else {
            echo " [ON]";
        }
        echo '(' . functions::display_date($mass['time']) . ')<br/>';
        $text = functions::checkout($mass['text'], 1, 1);
        if ($set_user['smileys'])
            $text = functions::smileys($text, $res['rights'] ? 1 : 0);
        echo '<div>' . $text . '</div>';
        if ($rights == 4 || $rights >= 6) {
            echo "$mass[ip] - $mass[soft]<br/><a href='index.php?act=delmes&amp;id=" . $mass['id'] . "'>(Удалить)</a><br/>";
        }
        echo "</div>";
    }
    ++$i;
}
if ($countm > $kmess) {
    echo "<hr/>";
    $ba = ceil($countm / $kmess);
    echo "Страницы:<br/>";    //TODO: Переделать на новый листинг по страницам
    $asd = $start - ($kmess);
    $asd2 = $start + ($kmess * 2);

    if ($start != 0) {
        echo '<a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
    }
    if ($asd < $countm && $asd > 0) {
        echo ' <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=1&amp;">1</a> .. ';
    }
    $page2 = $ba - $page;
    $pa = ceil($page / 2);
    $paa = ceil($page / 3);
    $pa2 = $page + floor($page2 / 2);
    $paa2 = $page + floor($page2 / 3);
    $paa3 = $page + (floor($page2 / 3) * 2);
    if ($page > 13) {
        echo ' <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="?id=' . $id . '&amp;page='
            . ($paa * 2) . '">' . ($paa * 2) . '</a> <a href="?id=' . $id . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
    } elseif ($page > 7) {
        echo ' <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="?id=' . $id . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
    }
    for ($i = $asd; $i < $asd2;) {
        if ($i < $countm && $i >= 0) {
            $ii = floor(1 + $i / $kmess);

            if ($start == $i) {
                echo " <b>$ii</b>";
            } else {
                echo ' <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . $ii . '">' . $ii . '</a> ';
            }
        }
        $i = $i + $kmess;
    }
    if ($page2 > 12) {
        echo ' .. <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) .
            '</a> .. <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
    } elseif ($page2 > 6) {
        echo ' .. <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
    }
    if ($asd2 < $countm) {
        echo ' .. <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . $ba . '">' . $ba . '</a>';
    }
    if ($countm > $start + $kmess) {
        echo ' <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
    }
    echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
        "'/><input type='hidden' name='act' value='komm'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
}
echo "<br/>" . $lng['total'] . ": $countm";
echo '<br/><a href="?act=view&amp;file=' . $id . '">' . $lng['back'] . '</a><br/>';