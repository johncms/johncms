<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Johncms\Api\ConfigInterface $config
 * @var PDO                         $db
 * @var Johncms\Api\UserInterface   $user
 */

require 'classes/download.php';

// Топ файлов
if ($id == 2) {
    $textl = _t('Most Commented');
} elseif ($id == 1) {
    $textl = _t('Most Downloaded');
} else {
    $textl = _t('Popular Files');
}

$linkTopComments = $config['mod_down_comm'] || $user->rights >= 7 ? '<br><a href="?act=top_files&amp;id=2">' . _t('Most Commented') . '</a>' : '';
echo '<div class="phdr"><a href="?"><b>' . _t('Downloads') . '</b></a> | ' . $textl . ' (' . $set_down['top'] . ')</div>';

if ($id == 2 && ($config['mod_down_comm'] || $user->rights >= 7)) {
    echo '<div class="gmenu"><a href="?act=top_files&amp;id=0">' . _t('Popular Files') . '</a><br>' .
        '<a href="?act=top_files&amp;id=1">' . _t('Most Downloaded') . '</a></div>';
    $sql = '`comm_count`';
} elseif ($id == 1) {
    echo '<div class="gmenu"><a href="?act=top_files&amp;id=0">' . _t('Popular Files') . '</a>' . $linkTopComments . '</div>';
    $sql = '`field`';
} else {
    echo '<div class="gmenu"><a href="?act=top_files&amp;id=1">' . _t('Most Downloaded') . '</a>' . $linkTopComments . '</div>';
    $sql = '`rate`';
}

// Выводим список
$req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = 2 ORDER BY ${sql} DESC LIMIT " . $set_down['top']);
$i = 0;

while ($res_down = $req_down->fetch()) {
    echo(($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down, 1) . '</div>';
}

echo '<div class="phdr"><a href="?">' . _t('Downloads') . '</a></div>';
echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
