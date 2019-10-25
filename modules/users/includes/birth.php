<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

define('_IN_JOHNCMS', 1);

$textl = _t('Birthdays');
$headmod = 'birth';
require 'system/head.php';

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Выводим список именинников
echo '<div class="phdr"><a href="index.php"><b>' . _t('Community') . '</b></a> | ' . _t('Birthdays') . '</div>';
$total = $db->query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . date('j', time()) . "' AND `monthb` = '" . date('n', time()) . "' AND `preg` = '1'")->fetchColumn();

if ($total) {
    $req = $db->query("SELECT * FROM `users` WHERE `dayb` = '" . date('j', time()) . "' AND `monthb` = '" . date('n', time()) . "' AND `preg` = '1' LIMIT ${start}, ${kmess}");

    while ($res = $req->fetch()) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        echo $tools->displayUser($res) . '</div>';
        ++$i;
    }

    echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

    if ($total > $kmess) {
        echo '<p>' . $tools->displayPagination('index.php?act=birth&amp;', $start, $total, $kmess) . '</p>';
        echo '<p><form action="index.php?act=birth" method="post">' .
             '<input type="text" name="page" size="2"/>' .
             '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
             '</form></p>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<p><a href="index.php">' . _t('Back') . '</a></p>';
