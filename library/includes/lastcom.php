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

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

echo '<div class="phdr"><strong><a href="?">' . _t('Library') . '</a></strong> | ' . _t('Latest comments') . '</div>';

$stmt = $db->query('SELECT `cms_library_comments`.`user_id` , `cms_library_comments`.`text` , `library_texts`.`name` , `library_texts`.`comm_count` , `library_texts`.`id` , `cms_library_comments`.`time` 
FROM `cms_library_comments` 
JOIN `library_texts` ON `cms_library_comments`.`sub_id` = `library_texts`.`id` 
GROUP BY `library_texts`.`id` 
ORDER BY `cms_library_comments`.`time` DESC 
LIMIT 20');

if ($stmt->rowCount()) {
    $i = 0;
    while ($row = $stmt->fetch()) {
        echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
            . (file_exists('../files/library/images/small/' . $row['id'] . '.png')
                ? '<div class="avatar"><img src="../files/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>'
                : '')
            . '<div class="righttable"><a href="?act=comments&amp;id=' . $row['id'] . '">' . $tools->checkout($row['name']) . '</a>'
            . '<div>' . $tools->checkout(substr($row['text'], 0, 500), 0, 2) . '</div></div>'
            . '<div class="sub">' . _t('Who added') . ': ' . $tools->checkout($db->query('SELECT `name` FROM `users` WHERE `id` = ' . $row['user_id'])->fetchColumn()) . ' (' . $tools->displayDate((int) $row['time']) . ')</div>'
            . '</div>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr"><a href="?">' . _t('Back') . '</a></div>' . PHP_EOL;
