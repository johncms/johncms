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

if (($systemUser->rights != 3 && $systemUser->rights < 6) || !$id) {
    header('Location: http://johncms.com?act=404');
    exit;
}

$req = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '$id'");

if ($req->rowCount()) {
    $res = $req->fetch();
    $db->exec("UPDATE `forum_topic` SET `deleted` = NULL, `deleted_by` = '" . $systemUser->name . "' WHERE `id` = '$id'");

    header('Location: index.php?type=topic&id=' . $id);
} else {
    header('Location: index.php');
}
