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

/**
 * @var PDO                        $db
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\Api\UserInterface  $user
 */

if ($user->rights == 3 || $user->rights >= 6) {
    $topic_vote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type`='1' AND `topic` = '${id}'")->fetchColumn();

    if ($topic_vote == 0) {
        echo $view->render('system::pages/result', [
            'title'         => _t('Delete Poll'),
            'type'          => 'alert-danger',
            'message'       => _t('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => _t('Back'),
        ]);
        exit;
    }

    if (isset($_GET['yes'])) {
        $db->exec("DELETE FROM `cms_forum_vote` WHERE `topic` = '${id}'");
        $db->exec("DELETE FROM `cms_forum_vote_users` WHERE `topic` = '${id}'");
        $db->exec("UPDATE `forum_topic` SET  `has_poll` = NULL  WHERE `id` = '${id}'");
        echo $view->render('system::pages/result', [
            'title'         => _t('Delete Poll'),
            'type'          => 'alert-success',
            'message'       => _t('Poll deleted'),
            'back_url'      => '/forum/?type=topic&id=' . $id,
            'back_url_name' => _t('Back'),
        ]);
        exit;
    } else {
        echo $view->render('forum::delete_poll', [
            'title'      => _t('Delete Poll'),
            'page_title' => _t('Delete Poll'),
            'id'         => $id,
            'back_url'   => '/forum/?type=topic&id=' . $id,
        ]);
    }
} else {
    http_response_code(403);
    echo $view->render('system::pages/result', [
        'title'         => _t('Access forbidden'),
        'type'          => 'alert-danger',
        'message'       => _t('Access forbidden'),
        'back_url'      => '/forum/',
        'back_url_name' => _t('Back'),
    ]);
}

exit; // TODO: Remove it later
