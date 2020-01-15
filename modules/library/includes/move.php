<?php

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Library\Utils;

defined('_IN_JOHNCMS') || die('Error: restricted access');

if ($adm) {
    /** @var PDO $db */
    $db = di(PDO::class);
    $stmt = $db->query('SELECT `id`, `pos` FROM `library_cats` WHERE ' . ($do === 'dir' ? '`parent` = ' . $id : '`parent` = 0') . ' ORDER BY `pos` ASC');
    $y = 0;
    $arrsort = [];

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetch()) {
            $y++;
            $arrsort[$y] = $row['id'] . '|' . $row['pos'];
        }
    }

    $type = isset($_GET['moveset']) && in_array($_GET['moveset'], ['up', 'down']) ? $_GET['moveset'] : Utils::redir404();
    $posid = isset($_GET['posid']) && $_GET['posid'] > 0 ? (int) ($_GET['posid']) : Utils::redir404();

    [$num1, $pos1] = explode('|', $arrsort[$posid]);
    [$num2, $pos2] = explode('|', $arrsort[($type === 'up' ? $posid - 1 : $posid + 1)]);

    $db->exec('UPDATE `library_cats` SET `pos` = ' . $pos2 . ' WHERE `id` = ' . $num1);
    $db->exec('UPDATE `library_cats` SET `pos` = ' . $pos1 . ' WHERE `id` = ' . $num2);

    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
