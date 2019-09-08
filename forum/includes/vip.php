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

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if ($systemUser->rights == 3 || $systemUser->rights >= 6) {
    if (empty($_GET['id'])) {
        require('../system/head.php');
        echo $tools->displayError(_t('Wrong data'));
        require('../system/end.php');
        exit;
    }

    if ($db->query("SELECT COUNT(*) FROM `forum` WHERE `id` = '" . $id . "' AND `type` = 't'")->fetchColumn()) {
        $db->exec("UPDATE `forum` SET  `vip` = '" . (isset($_GET['vip']) ? '1' : '0') . "' WHERE `id` = '$id'");
        header('Location: index.php?id=' . $id);
    } else {
        require('../system/head.php');
        echo $tools->displayError(_t('Wrong data'));
        require('../system/end.php');
        exit;
    }
}
