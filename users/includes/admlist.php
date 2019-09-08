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

$textl = _t('Administration');
$headmod = 'admlist';
require('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Выводим список администрации
echo '<div class="phdr"><a href="index.php"><b>' . _t('Community') . '</b></a> | ' . _t('Administration') . '</div>';
$total = $db->query("SELECT COUNT(*) FROM `users` WHERE `rights` >= 1")->fetchColumn();
$req = $db->query("SELECT `id`, `name`, `sex`, `lastdate`, `datereg`, `status`, `rights`, `ip`, `browser`, `rights` FROM `users` WHERE `rights` >= 1 ORDER BY `rights` DESC LIMIT $start, $kmess");

for ($i = 0; $res = $req->fetch(); ++$i) {
    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
    echo $tools->displayUser($res) . '</div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<p>' . $tools->displayPagination('index.php?act=admlist&amp;', $start, $total, $kmess) . '</p>' .
        '<p><form action="index.php?act=admlist" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
        '</form></p>';
}

echo'<p><a href="index.php?act=search">' . _t('User Search') . '</a><br />' .
    '<a href="index.php">' . _t('Back') . '</a></p>';
