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

$stmt = $db->query("SELECT * FROM `download` WHERE type='file' AND id='" . $id . "' LIMIT 1");
if (!$stmt->rowCount()) {
    echo "ERROR<br/><a href='?'>Back</a><br/>";
    require_once('../incfiles/end.php');
    exit;
}

if (!$set['mod_down_comm'] && $rights < 7) {
    echo '<p>ERROR<br/><a href="index.php">Back</a></p>';
    require_once('../incfiles/end.php');
    exit;
}
$fayl1 = $stmt->fetch();

$total = $db->query('SELECT COUNT(*) FROM `download` WHERE `type` = "komm" AND `refid` = "' . $id . '"')->fetchColumn();

echo '<p>' . $lng['comments'] . ": <span class='red'>$fayl1[name]</span></p>";
if ($user_id && !isset($ban['1']) && !isset($ban['10'])) {
    echo "<a href='?act=addkomm&amp;id=" . $id . "'>Написать</a><br/>";
}

if ($total < $start + $kmess) {
    $end = $total;
} else {
    $end = $start + $kmess;
}

$stmt_u = $db->prepare('SELECT * FROM `users` WHERE `name`= ? LIMIT 1;');

$stmt = $db->query("SELECT * FROM `download` WHERE type='komm' AND refid='" . $id . "' ORDER BY time DESC ;");
while ($mass = $stmt->fetch())
{
    $stmt_u->execute([$mass['avtor']]);
    $mass1 = $stmt_u->fetch();
    echo '<div class="list' . (++$i % 2 + 1) . '">';
    if ($user_id && $user_id != $mass1['id']) {
        echo "<a href='../users/profile.php?user=" . $mass1['id'] . "'>$mass[avtor]</a>";
    } else {
        echo "$mass[avtor]";
    }
    switch ($mass1['rights']) {
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
    $ontime = $mass1['lastdate'];
    $ontime2 = $ontime + 300;
    if (time() > $ontime2) {
        echo " [Off]";
    } else {
        echo " [ON]";
    }
    echo '(' . functions::display_date($mass['time']) . ')<br/>';
    $text = functions::checkout($mass['text'], 1, 1);
    if ($set_user['smileys']) {
        $text = functions::smileys($text, $res['rights'] ? 1 : 0);
    }
    echo '<div>' . $text . '</div>';
    if ($rights == 4 || $rights >= 6) {
        echo "$mass[ip] - $mass[soft]<br/><a href='index.php?act=delmes&amp;id=" . $mass['id'] . "'>(Удалить)</a><br/>";
    }
    echo "</div>";
}
$stmt_u = null;
if ($total > $kmess) {
    echo "<hr/>";
    echo functions::display_pagination('index.php?act=komm&amp;id=' . $id . '&amp;', $start, $total, $kmess);
    echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
        "'/><input type='hidden' name='act' value='komm'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
}
echo "<br/>" . $lng['total'] . ": $total";
echo '<br/><a href="?act=view&amp;file=' . $id . '">' . $lng['back'] . '</a><br/>';