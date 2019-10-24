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
    $topic_vote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type`='1' AND `topic` = '${id}'")->fetchColumn();
    require '../system/head.php';

    if ($topic_vote == 0) {
        echo $tools->displayError(_t('Wrong data'));
        require '../system/end.php';
        exit;
    }

    if (isset($_GET['yes'])) {
        $db->exec("DELETE FROM `cms_forum_vote` WHERE `topic` = '${id}'");
        $db->exec("DELETE FROM `cms_forum_vote_users` WHERE `topic` = '${id}'");
        $db->exec("UPDATE `forum_topic` SET  `has_poll` = NULL  WHERE `id` = '${id}'");
        echo _t('Poll deleted') . '<br /><a href="' . $_SESSION['prd'] . '">' . _t('Continue') . '</a>';
    } else {
        echo '<p>' . _t('Do you really want to delete a poll?') . '</p>';
        echo '<p><a href="?act=delvote&amp;id=' . $id . '&amp;yes">' . _t('Delete') . '</a><br />';
        echo '<a href="' . htmlspecialchars(getenv('HTTP_REFERER')) . '">' . _t('Cancel') . '</a></p>';
        $_SESSION['prd'] = htmlspecialchars(getenv('HTTP_REFERER'));
    }
} else {
    header('location: ../index.php?err');
}
