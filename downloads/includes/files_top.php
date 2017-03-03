<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

require '../system/head.php';
require 'classes/download.php';

// Топ файлов
if ($id == 2) {
    $textl = _t('Most Commented');
} elseif ($id == 1) {
    $textl = _t('Most Downloaded');
} else {
    $textl = _t('Popular Files');
}

$linkTopComments = $config['mod_down_comm'] || $systemUser->rights >= 7 ? '<br><a href="?act=top_files&amp;id=2">' . _t('Most Commented') . '</a>' : '';
echo '<div class="phdr"><a href="?"><b>' . _t('Downloads') . '</b></a> | ' . $textl . ' (' . $set_down['top'] . ')</div>';

if ($id == 2 && ($config['mod_down_comm'] || $systemUser->rights >= 7)) {
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
$req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = 2 ORDER BY $sql DESC LIMIT " . $set_down['top']);
$i = 0;

while ($res_down = $req_down->fetch()) {
    echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down, 1) . '</div>';
}

echo '<div class="phdr"><a href="?">' . _t('Downloads') . '</a></div>';
require '../system/end.php';
