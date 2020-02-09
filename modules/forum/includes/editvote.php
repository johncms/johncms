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
    $topic_vote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type`='1' AND `topic`='${id}'")->fetchColumn();

    if ($topic_vote == 0) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Edit Poll'),
                'page_title'    => __('Edit Poll'),
                'type'          => 'alert-danger',
                'message'       => __('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    if (isset($_GET['delvote']) && ! empty($_GET['vote'])) {
        $vote = abs((int) ($_GET['vote']));
        $totalvote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '2' AND `id` = '${vote}' AND `topic` = '${id}'")->fetchColumn();
        $countvote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '2' AND `topic` = '${id}'")->fetchColumn();

        if ($countvote <= 2) {
            header('location: ?act=editvote&id=' . $id . '');
        }

        if ($totalvote != 0) {
            if (isset($_GET['yes'])) {
                $db->exec("DELETE FROM `cms_forum_vote` WHERE `id` = '${vote}'");
                $countus = $db->query("SELECT COUNT(*) FROM `cms_forum_vote_users` WHERE `vote` = '${vote}' AND `topic` = '${id}'")->fetchColumn();
                $topic_vote = $db->query("SELECT `count` FROM `cms_forum_vote` WHERE `type` = '1' AND `topic` = '${id}' LIMIT 1")->fetch();
                $totalcount = $topic_vote['count'] - $countus;
                $db->exec("UPDATE `cms_forum_vote` SET  `count` = '${totalcount}'   WHERE `type` = '1' AND `topic` = '${id}'");
                $db->exec("DELETE FROM `cms_forum_vote_users` WHERE `vote` = '${vote}'");
                header('location: ?act=editvote&id=' . $id . '');
            } else {
                echo $view->render(
                    'forum::delete_answer',
                    [
                        'title'      => __('Delete Answer'),
                        'page_title' => __('Delete Answer'),
                        'id'         => $id,
                        'delete_url' => '?act=editvote&amp;id=' . $id . '&amp;vote=' . $vote . '&amp;delvote&amp;yes',
                        'back_url'   => '?act=editvote&id=' . $id,
                    ]
                );
                exit;
            }
        } else {
            header('location: ?act=editvote&id=' . $id . '');
        }
    } else {
        if (isset($_POST['submit'])) {
            $vote_name = mb_substr(trim($_POST['name_vote']), 0, 50);

            if (! empty($vote_name)) {
                $db->exec('UPDATE `cms_forum_vote` SET  `name` = ' . $db->quote($vote_name) . "  WHERE `topic` = '${id}' AND `type` = '1'");
            }

            $vote_result = $db->query("SELECT `id` FROM `cms_forum_vote` WHERE `type`='2' AND `topic`='" . $id . "'");

            while ($vote = $vote_result->fetch()) {
                if (! empty($_POST[$vote['id'] . 'vote'])) {
                    $text = mb_substr(trim($_POST[$vote['id'] . 'vote']), 0, 30);
                    $db->exec('UPDATE `cms_forum_vote` SET  `name` = ' . $db->quote($text) . "  WHERE `id` = '" . $vote['id'] . "'");
                }
            }

            $countvote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type`='2' AND `topic`='" . $id . "'")->fetchColumn();

            for ($vote = $countvote; $vote < 20; $vote++) {
                if (! empty($_POST[$vote])) {
                    $text = mb_substr(trim($_POST[$vote]), 0, 30);
                    $db->exec('INSERT INTO `cms_forum_vote` SET `name` = ' . $db->quote($text) . ",  `type` = '2', `topic` = '${id}'");
                }
            }
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Edit Poll'),
                    'page_title'    => __('Edit Poll'),
                    'type'          => 'alert-success',
                    'message'       => __('Poll changed'),
                    'back_url'      => '/forum/?type=topic&amp;id=' . $id,
                    'back_url_name' => __('Continue'),
                ]
            );
            exit;
        }
        // Форма редактирования опроса
        $countvote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '2' AND `topic` = '${id}'")->fetchColumn();
        $topic_vote = $db->query("SELECT `name` FROM `cms_forum_vote` WHERE `type` = '1' AND `topic` = '${id}' LIMIT 1")->fetch();
        $vote_result = $db->query("SELECT `id`, `name` FROM `cms_forum_vote` WHERE `type` = '2' AND `topic` = '${id}'");

        $votes = [];
        $i = 0;
        while ($vote = $vote_result->fetch()) {
            $votes[] = [
                'input_name'  => $vote['id'] . 'vote',
                'input_label' => __('Answer') . ' ' . ($i + 1),
                'input_value' => htmlentities($vote['name'], ENT_QUOTES, 'UTF-8'),
                'delete_url'  => $countvote > 2 ? '?act=editvote&amp;id=' . $id . '&amp;vote=' . $vote['id'] . '&amp;delvote' : '',
            ];
            ++$i;
        }

        $count_vote = isset($_POST['count_vote']) ? (int) $_POST['count_vote'] : $countvote;
        if ($countvote < 20) {
            if (isset($_POST['plus'])) {
                ++$count_vote;
            } elseif (isset($_POST['minus'])) {
                --$count_vote;
            }

            if (empty($count_vote)) {
                $count_vote = $countvote;
            } elseif ($count_vote > 20) {
                $count_vote = 20;
            }

            for ($vote = $i; $vote < $count_vote; $vote++) {
                $votes[] = [
                    'input_name'  => $vote,
                    'input_label' => __('Answer') . ' ' . ($vote + 1),
                    'input_value' => htmlentities($_POST[$vote] ?? '', ENT_QUOTES, 'UTF-8'),
                ];
            }
        }

        echo $view->render(
            'forum::edit_poll',
            [
                'title'      => __('Edit Poll'),
                'page_title' => __('Edit Poll'),
                'id'         => $id,
                'back_url'   => '?type=topic&id=' . $id,
                'saved_vote' => $countvote,
                'count_vote' => $count_vote,
                'poll_name'  => htmlentities($topic_vote['name'], ENT_QUOTES, 'UTF-8'),
                'votes'      => $votes,
            ]
        );
    }
}
