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

if ($systemUser->rights == 4 || $systemUser->rights >= 6) {
    $req_down = $db->query("SELECT `dir`, `name`, `id` FROM `download__category`");

    while ($res_down = $req_down->fetch()) {
        $dir_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `dir` LIKE '" . ($res_down['dir']) . "%'")->fetchColumn();
        $db->exec("UPDATE `download__category` SET `total` = '$dir_files' WHERE `id` = '" . $res_down['id'] . "'");
    }
}

header('Location: ?id=' . $id);
