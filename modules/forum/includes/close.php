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

if (($systemUser->rights != 3 && $systemUser->rights < 6) || ! $id) {
    header('Location: ./');
    exit;
}

if ($db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `id` = '${id}'")->fetchColumn()) {
    if (isset($_GET['closed'])) {
        $db->exec("UPDATE `forum_topic` SET `closed` = '1' WHERE `id` = '${id}'");
    } else {
        $db->exec("UPDATE `forum_topic` SET `closed` = '0' WHERE `id` = '${id}'");
    }
}

header("Location: ?type=topic&id=${id}");
