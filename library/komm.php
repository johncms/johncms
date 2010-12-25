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

if (!$id) {
    echo '<p>ERROR<br/><a href="index.php">Back</a></p>';
    require_once('../incfiles/end.php');
    exit;
}
if (!$set['mod_lib_comm'] && $rights < 7) {
    echo '<p>' . $lng['comments_closed'] . '<br/><a href="index.php">' . $lng['back'] . '</a></p>';
    require_once('../incfiles/end.php');
    exit;
}
// Запрос имени статьи
$req = mysql_query("SELECT `name` FROM `lib` WHERE `type` = 'bk' AND `id` = '" . $id . "' LIMIT 1");
if (mysql_num_rows($req) != 1) {
    // если статья не существует, останавливаем скрипт
    echo '<p>ERROR<br/><a href="index.php">Back</a></p>';
    require_once('../incfiles/end.php');
    exit;
}
$article = mysql_fetch_array($req);
// Запрос числа каментов
$req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'komm' AND `refid` = '" . $id . "'");
$countm = mysql_result($req, 0);
echo '<div class="phdr">' . $lng_lib['comment_article'] . ':<br /><b>' . htmlentities($article['name'], ENT_QUOTES, 'UTF-8') . '</b></div>';
if ($user_id && !$ban['1'] && !$ban['10']) {
    echo '<div class="gmenu"><a href="index.php?act=addkomm&amp;id=' . $id . '">' . $lng['write'] . '</a></div>';
}
// Запрос списка комментариев
$mess = mysql_query("SELECT * FROM `lib` WHERE `type` = 'komm' AND `refid` = '" . $id . "' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
while ($mass = mysql_fetch_array($mess)) {
    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
    $uz = mysql_query("select * from `users` where name='" . functions::check($mass['avtor']) . "';");
    $mass1 = mysql_fetch_array($uz);
    if ((!empty($_SESSION['uid'])) && ($_SESSION['uid'] != $mass1['id'])) {
        echo "<a href='../users/profile.php?user=" . $mass1['id'] . "'>$mass[avtor]</a>";
    } else {
        echo $mass['avtor'];
    }
    $vr = $mass['time'] + $set_user['sdvig'] * 3600;
    $vr1 = date("d.m.Y / H:i", $vr);
    switch ($mass1['rights']) {
        case 7:
            echo ' Adm ';
            break;

        case 6:
            echo ' Smd ';
            break;

        case 5:
            echo ' Mod ';
            break;

        case 1:
            echo ' Kil ';
            break;
    }
    $ontime = $mass1['lastdate'];
    $ontime2 = $ontime + 300;
    if ($realtime > $ontime2) {
        echo '<font color="#FF0000"> [Off]</font>';
    } else {
        echo '<font color="#00AA00"> [ON]</font>';
    }
    echo "($vr1)<br/>";
    if ($set_user['smileys']) {
        $tekst = functions::smileys($mass['text'], ($mass['from'] == $nickadmina || $mass['from'] == $nickadmina2 || $mass1['rights'] >= 1) ? 1 : 0);
    } else {
        $tekst = $mass['text'];
    }
    echo "$tekst<br/>";
    if ($rights == 5 || $rights >= 6) {
        echo long2ip($mass['ip']) . " - $mass[soft]<br/><a href='index.php?act=del&amp;id=" . $mass['id'] . "'>" . $lng['delete'] . "</a>";
    }
    echo '</div>';
    ++$i;
}
echo '<div class="phdr">' . $lng['total'] . ': ' . $countm . '</div>';
// Навигация по страницам
if ($countm > $kmess) {
    echo '<p>' . functions::display_pagination('index.php?act=komm&amp;id=' . $id . '&amp;', $start, $countm, $kmess) . '</p>';
    echo '<p><form action="index.php" method="get"><input type="hidden" name="act" value="komm"/><input type="hidden" name="id" value="' . $id .
        '"/><input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
}
echo '<p><a href="?id=' . $id . '">' . $lng['to_article'] . '</a></p>';

?>
