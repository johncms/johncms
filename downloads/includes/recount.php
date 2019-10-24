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

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

if ($systemUser->rights == 4 || $systemUser->rights >= 6) {
    $req_down = $db->query('SELECT `dir`, `name`, `id` FROM `download__category`');

    while ($res_down = $req_down->fetch()) {
        $dir_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `dir` LIKE '" . ($res_down['dir']) . "%'")->fetchColumn();
        $db->exec("UPDATE `download__category` SET `total` = '${dir_files}' WHERE `id` = '" . $res_down['id'] . "'");
    }
}

header('Location: ?id=' . $id);
