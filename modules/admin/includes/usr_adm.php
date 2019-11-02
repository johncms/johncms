<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNADM') || die('Error: restricted access');

$sw = 0;
$adm = 0;
$smd = 0;
$mod = 0;

$tools = $container->get(Johncms\Api\ToolsInterface::class);

ob_start();

echo '<div class="phdr"><a href="./"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Administration') . '</div>';
$req = $db->query("SELECT * FROM `users` WHERE `rights` = '9'");

if ($req->rowCount()) {
    echo '<div class="bmenu">' . _t('Supervisors') . '</div>';
    while ($res = $req->fetch()) {
        echo ($sw % 2) ? '<div class="list2">' : '<div class="list1">';
        echo $tools->displayUser($res, ['header' => ('<b>ID:' . $res['id'] . '</b>')]);
        echo '</div>';
        ++$sw;
    }
}

$req = $db->query("SELECT * FROM `users` WHERE `rights` = '7' ORDER BY `name` ASC");

if ($req->rowCount()) {
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
    '<p><a href="./">' . _t('Admin Panel') . '</a></p>';

echo $view->render('system::app/old_content', [
    'title'   => _t('Admin Panel'),
    'content' => ob_get_clean(),
]);
