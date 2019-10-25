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

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if ($systemUser->rights == 3 || $systemUser->rights >= 6) {
    if (empty($_GET['id'])) {
        require 'system/head.php';
        echo $tools->displayError(_t('Wrong data'));
        require 'system/end.php';
        exit;
    }

    if ($db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `id` = '" . $id . "'")->fetchColumn()) {
        $db->exec("UPDATE `forum_topic` SET  `pinned` = '" . (isset($_GET['vip']) ? '1' : null) . "' WHERE `id` = '${id}'");
        header('Location: index.php?type=topic&id=' . $id);
    } else {
        require 'system/head.php';
        echo $tools->displayError(_t('Wrong data'));
        require 'system/end.php';
        exit;
    }
}
