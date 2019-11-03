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

/** @var Johncms\Api\UserInterface $user */
$user = $container->get(Johncms\Api\UserInterface::class);

if ($user->isValid()) {
    /** @var PDO $db */
    $db = $container->get(PDO::class);

    /** @var Johncms\Api\ToolsInterface $tools */
    $tools = $container->get(Johncms\Api\ToolsInterface::class);

    $topic = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `id` = '${id}' AND (`deleted` != '1' OR `deleted` IS NULL)")->fetchColumn();
    $vote = abs((int) ($_POST['vote']));
    $topic_vote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '2' AND `id` = '${vote}' AND `topic` = '${id}'")->fetchColumn();
    $vote_user = $db->query("SELECT COUNT(*) FROM `cms_forum_vote_users` WHERE `user` = '" . $user->id . "' AND `topic` = '${id}'")->fetchColumn();
    require 'system/head.php';

    if ($topic_vote == 0 || $vote_user > 0 || $topic == 0) {
        echo $tools->displayError(_t('Wrong data'));
        require 'system/end.php';
        exit;
    }

    $db->exec("INSERT INTO `cms_forum_vote_users` SET `topic` = '${id}', `user` = '" . $user->id . "', `vote` = '${vote}'");
    $db->exec("UPDATE `cms_forum_vote` SET `count` = count + 1 WHERE id = '${vote}'");
    $db->exec("UPDATE `cms_forum_vote` SET `count` = count + 1 WHERE topic = '${id}' AND `type` = '1'");
    echo _t('Vote accepted') . '<br /><a href="' . htmlspecialchars(getenv('HTTP_REFERER')) . '">' . _t('Back') . '</a>';
}
