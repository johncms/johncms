<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 */

$req_down = $db->query('SELECT `dir`, `name`, `id` FROM `download__category`');

while ($res_down = $req_down->fetch()) {
    $dir_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `dir` LIKE '" . ($res_down['dir']) . "%'")->fetchColumn();
    $db->exec("UPDATE `download__category` SET `total` = '${dir_files}' WHERE `id` = '" . $res_down['id'] . "'");
}

header('Location: ?id=' . $id);
