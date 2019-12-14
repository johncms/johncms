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

if (($user->rights != 3 && $user->rights < 6) || ! $id) {
    header('Location: http://johncms.com?act=404');
    exit;
}

$req = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${id}'");

if ($req->rowCount()) {
    $res = $req->fetch();
    $db->exec("UPDATE `forum_topic` SET `deleted` = NULL, `deleted_by` = '" . $user->name . "' WHERE `id` = '${id}'");

    header('Location: ?type=topic&id=' . $id);
} else {
    header('Location: ./');
}
