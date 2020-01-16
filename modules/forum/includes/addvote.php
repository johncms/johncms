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
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

if ($user->rights == 3 || $user->rights >= 6) {
    $topic = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `id`='${id}' AND (`deleted` != '1' OR `deleted` IS NULL)")->fetchColumn();
    $topic_vote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type`='1' AND `topic`='${id}'")->fetchColumn();

    if ($topic_vote != 0 || $topic == 0) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Add Poll'),
                'page_title'    => __('Add Poll'),
                'type'          => 'alert-danger',
                'message'       => __('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    if (isset($_POST['submit'])) {
        $vote_name = mb_substr(trim($_POST['name_vote']), 0, 50);

        if (! empty($vote_name) && ! empty($_POST[0]) && ! empty($_POST[1]) && ! empty($_POST['count_vote'])) {
            $db->exec(
                'INSERT INTO `cms_forum_vote` SET
                `name`=' . $db->quote($vote_name) . ",
                `time`='" . time() . "',
                `type` = '1',
                `topic`='${id}'
            "
            );
            $db->exec("UPDATE `forum_topic` SET `has_poll` = '1'  WHERE `id` = '${id}'");
            $vote_count = abs((int) ($_POST['count_vote']));

            if ($vote_count > 20) {
                $vote_count = 20;
            } else {
                if ($vote_count < 2) {
                    $vote_count = 2;
                }
            }

            for ($vote = 0; $vote < $vote_count; $vote++) {
                $text = mb_substr(trim($_POST[$vote]), 0, 30);

                if (empty($text)) {
                    continue;
                }

                $db->exec(
                    'INSERT INTO `cms_forum_vote` SET
                    `name`=' . $db->quote($text) . ",
                    `type` = '2',
                    `topic`='${id}'
                "
                );
            }
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Add Poll'),
                    'page_title'    => __('Add Poll'),
                    'type'          => 'alert-success',
                    'message'       => __('Poll added'),
                    'back_url'      => '/forum/?type=topic&amp;id=' . $id,
                    'back_url_name' => __('Continue'),
                ]
            );
        } else {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Add Poll'),
                    'page_title'    => __('Add Poll'),
                    'type'          => 'alert-danger',
                    'message'       => __('The required fields are not filled'),
                    'back_url'      => '/forum/?act=addvote&amp;id=' . $id,
                    'back_url_name' => __('Repeat'),
                ]
            );
        }
        exit;
    }
    $count_vote = isset($_POST['count_vote']) ? (int) $_POST['count_vote'] : 0;

    if (isset($_POST['plus'])) {
        ++$count_vote;
    } elseif (isset($_POST['minus'])) {
        --$count_vote;
    }

    if (empty($_POST['count_vote']) || $_POST['count_vote'] < 2) {
        $count_vote = 2;
    } elseif ($_POST['count_vote'] > 20) {
        $count_vote = 20;
    }

    $votes = [];
    for ($vote = 0; $vote < $count_vote; $vote++) {
        $votes[] = [
            'input_name'  => $vote,
            'input_label' => __('Answer') . ' ' . ($vote + 1),
            'input_value' => htmlentities($_POST[$vote] ?? '', ENT_QUOTES, 'UTF-8'),
        ];
    }

    echo $view->render(
        'forum::add_poll',
        [
            'title'      => __('Add File'),
            'page_title' => __('Add File'),
            'id'         => $id,
            'back_url'   => '?type=topic&id=' . $id,
            'count_vote' => $count_vote,
            'poll_name'  => htmlentities($_POST['name_vote'] ?? '', ENT_QUOTES, 'UTF-8'),
            'votes'      => $votes,
        ]
    );
} else {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Access forbidden'),
            'type'          => 'alert-danger',
            'message'       => __('Access forbidden'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Back'),
        ]
    );
}
