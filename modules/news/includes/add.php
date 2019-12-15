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
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\View\Render $view
 * @var \Johncms\Utility\NavChain $nav_chain
 */

// Add news
$nav_chain->add(_t('Add news'), '');

if ($user->rights >= 6) {
    if (! empty($_POST)) {
        $error = [];
        $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : false;
        $text = isset($_POST['text']) ? trim($_POST['text']) : false;

        if (! $name) {
            $error[] = _t('You have not entered news title');
        }

        if (! $text) {
            $error[] = _t('You have not entered news text');
        }

        $flood = $tools->antiflood();

        if ($flood) {
            $error[] = sprintf(_t('You cannot add the message so often. Please, wait %d seconds.'), $flood);
        }

        if (! $error) {
            $rid = 0;

            if (! empty($_POST['rz'])) {
                $rz = (int) $_POST['rz'];
                $pr = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${rz}'");
                if ($pr1 = $pr->fetch()) {
                    $date = new DateTime();
                    $date = $date->format('Y-m-d H:i:s');

                    $db->prepare(
                        '
                                  INSERT INTO `forum_topic` SET
                                  `section_id` = ?,
                                  `created_at` = ?,
                                  `user_id` = ?,
                                  `user_name` = ?,
                                  `name` = ?,
                                  `last_post_date` = ?,
                                  `post_count` = 0
                                '
                    )->execute(
                        [
                            $pr1['id'],
                            $date,
                            $user->id,
                            $user->name,
                            $name,
                            time(),
                        ]
                    );

                    /** @var Johncms\System\Http\Environment $env */
                    $env = di(Johncms\System\Http\Environment::class);
                    $rid = $db->lastInsertId();

                    $db->prepare(
                        '
                                  INSERT INTO `forum_messages` SET
                                  `topic_id` = ?,
                                  `date` = ?,
                                  `user_id` = ?,
                                  `user_name` = ?,
                                  `ip` = ?,
                                  `ip_via_proxy` = ?,
                                  `user_agent` = ?,
                                  `text` = ?
                                '
                    )->execute(
                        [
                            $rid,
                            time(),
                            $user->id,
                            $user->name,
                            $env->getIp(),
                            $env->getIpViaProxy(),
                            $env->getUserAgent(),
                            $text,
                        ]
                    );
                    $tools->recountForumTopic($rid);
                }
            }

            $db->prepare(
                '
                      INSERT INTO `news` SET
                      `time` = ?,
                      `avt` = ?,
                      `name` = ?,
                      `text` = ?,
                      `kom` = ?
                    '
            )->execute(
                [
                    time(),
                    $user->name,
                    $name,
                    $text,
                    $rid,
                ]
            );

            $db->exec('UPDATE `users` SET `lastpost` = ' . time() . ' WHERE `id` = ' . $user->id);
            echo $view->render(
                'system::pages/result',
                [
                    'title'    => _t('Add news'),
                    'message'  => _t('News added'),
                    'type'     => 'alert-success',
                    'back_url' => '/news/',
                ]
            );
        } else {
            echo $view->render(
                'system::pages/result',
                [
                    'title'    => _t('Add news'),
                    'message'  => $error,
                    'type'     => 'alert-danger',
                    'back_url' => '/news/add/',
                ]
            );
        }
    } else {
        $discussion_items = [];

        // Putting an array of discussion forums
        $fr = $db->query('SELECT * FROM `forum_sections` WHERE `section_type` = 0');
        while ($fr1 = $fr->fetch()) {
            $sections = [];
            $pr = $db->query("SELECT * FROM `forum_sections` WHERE `section_type` = 1 AND `parent` = '" . $fr1['id'] . "'");
            while ($pr1 = $pr->fetch()) {
                $sections[] = [
                    'id'   => $pr1['id'],
                    'name' => $pr1['name'],
                ];
            }
            $parent = [
                'id'       => $fr1['id'],
                'name'     => $fr1['name'],
                'sections' => $sections,
            ];
            $discussion_items[] = $parent;
        }

        echo $view->render(
            'news::add',
            [
                'discussions' => $discussion_items,
            ]
        );
    }
} else {
    pageNotFound();
}
