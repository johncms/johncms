<?php

defined('_IN_JOHNADM') or die('Error: restricted access');

$sw = 0;
$adm = 0;
$smd = 0;
$mod = 0;
echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Administration') . '</div>';
$req = $db->query("SELECT * FROM `users` WHERE `rights` = '9' ORDER BY `name` ASC");

if ($req->rowCount()) {
    echo '<div class="bmenu">' . _t('Supervisors') . '</div>';
    while ($res = $req->fetch()) {
        echo $sw % 2 ? '<div class="list2">' : '<div class="list1">';
        echo $tools->displayUser($res, ['header' => ('<b>ID:' . $res['id'] . '</b>')]);
        echo '</div>';
        ++$sw;
    }
}

$req = $db->query("SELECT * FROM `users` WHERE `rights` = '7' ORDER BY `name` ASC");

if ($req->fetch()) {
    echo '<div class="bmenu">' . _t('Administrators') . '</div>';

    while ($res = $req->fetch()) {
        echo $adm % 2 ? '<div class="list2">' : '<div class="list1">';
        echo $tools->displayUser($res, ['header' => ('<b>ID:' . $res['id'] . '</b>')]);
        echo '</div>';
        ++$adm;
    }
}

$req = $db->query("SELECT * FROM `users` WHERE `rights` = '6' ORDER BY `name` ASC");

if ($req->rowCount()) {
    echo '<div class="bmenu">' . _t('Super Moderators') . '</div>';

    while ($res = $req->fetch()) {
        echo $smd % 2 ? '<div class="list2">' : '<div class="list1">';
        echo $tools->displayUser($res, ['header' => ('<b>ID:' . $res['id'] . '</b>')]);
        echo '</div>';
        ++$smd;
    }
}

$req = $db->query("SELECT * FROM `users` WHERE `rights` BETWEEN '1' AND '5' ORDER BY `name` ASC");

if ($req->rowCount()) {
    echo '<div class="bmenu">' . _t('Moderators') . '</div>';

    while ($res = $req->fetch()) {
        echo $mod % 2 ? '<div class="list2">' : '<div class="list1">';
        echo $tools->displayUser($res, ['header' => ('<b>ID:' . $res['id'] . '</b>')]);
        echo '</div>';
        ++$mod;
    }
}

echo '<div class="phdr">' . _t('Total') . ': ' . ($sw + $adm + $smd + $mod) . '</div>' .
    '<p><a href="index.php">' . _t('Admin Panel') . '</a></p>';
