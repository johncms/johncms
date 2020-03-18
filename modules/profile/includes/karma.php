<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Notifications\Notification;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$title = __('Karma');
$set_karma = $config['karma'];
$data = [];
$nav_chain->add(__('User Profile'), '?user=' . $user_data['id']);
$nav_chain->add(__('Karma'));

$post = $request->getParsedBody();

if ($set_karma['on']) {
    /** @var PDO $db */
    $db = di(PDO::class);

    /** @var Johncms\System\Users\User $user */
    $user = di(Johncms\System\Users\User::class);

    /** @var Johncms\System\Legacy\Tools $tools */
    $tools = di(Johncms\System\Legacy\Tools::class);

    switch ($mod) {
        case 'vote':
            // Отдаем голос за пользователя
            if (! $user->karma_off && empty($user->ban)) {
                $error = [];

                if ($user_data['rights'] && $set_karma['adm']) {
                    $error[] = __('It is forbidden to vote for administration');
                }

                if ($user_data['ip'] === di(Johncms\System\Http\Environment::class)->getIp()) {
                    $error[] = __('Cheating karma is forbidden');
                }

                if ($user->datereg > (time() - 604800) || $user->postforum < $set_karma['forum']) {
                    $error[] = sprintf(
                        __('Users can take part in voting if they have stayed on a site not less %s and their score on the forum %d posts.'),
                        '7 ' . __('days'),
                        $set_karma['forum']
                    );
                }

                $count = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `user_id` = '" . $user->id . "' AND `karma_user` = '" . $user_data['id'] . "' AND `time` > '" . (time() - 86400) . "'")->fetchColumn();

                if ($count) {
                    $error[] = __('You can vote for single user just one time for 24 hours');
                }

                $sum = $db->query("SELECT SUM(`points`) FROM `karma_users` WHERE `user_id` = '" . $user->id . "' AND `time` >= '" . $user->karma_time . "'")->fetchColumn();

                if (($set_karma['karma_points'] - $sum) <= 0) {
                    $error[] = sprintf(__('You have exceeded the limit of votes. New voices will be added %s'), date('d.m.y в H:i:s', ($user->karma_time + 86400)));
                }

                if ($error) {
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'         => $title,
                            'type'          => 'alert-danger',
                            'message'       => $error,
                            'back_url'      => '?user=' . $user_data['id'],
                            'back_url_name' => __('Back'),
                        ]
                    );
                } elseif (isset($_POST['submit'])) {
                    $text = isset($_POST['text']) ? mb_substr(trim($_POST['text']), 0, 500) : '';
                    $type = (int) ($_POST['type']) ? 1 : 0;
                    $points = abs((int) ($_POST['points']));

                    if (! $points || $points > ($set_karma['karma_points'] - $sum)) {
                        $points = 1;
                    }

                    $db->prepare(
                        '
                          INSERT INTO `karma_users` SET
                          `user_id` = ?,
                          `name` = ?,
                          `karma_user` = ?,
                          `points` = ?,
                          `type` = ?,
                          `time` = ?,
                          `text` = ?
                        '
                    )->execute(
                        [
                            $user->id,
                            $user->name,
                            $user_data['id'],
                            $points,
                            $type,
                            time(),
                            $text,
                        ]
                    );

                    $sql = $type ? "`karma_plus` = '" . ($user_data['karma_plus'] + $points) . "'" : "`karma_minus` = '" . ($user_data['karma_minus'] + $points) . "'";
                    $db->query("UPDATE `users` SET ${sql} WHERE `id` = " . $user_data['id']);

                    // Добавляем уведомление пользователю
                    (new Notification())->create(
                        [
                            'module'     => 'karma',
                            'event_type' => 'new_vote',
                            'user_id'    => $user_data->id,
                            'sender_id'  => $user->id,
                            'fields'     => [
                                'user_name'   => htmlspecialchars($user->name),
                                'karma_url'   => '/profile/?act=karma&user=' . $user_data->id . '&type=2',
                                'vote_points' => ($type ? '+' : '-') . $points,
                                'message'     => $tools->smilies($tools->checkout($text)),
                            ],
                        ]
                    );

                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'         => $title,
                            'type'          => 'alert-success',
                            'message'       => __('You have successfully voted'),
                            'back_url'      => '?user=' . $user_data['id'],
                            'back_url_name' => __('Continue'),
                        ]
                    );
                } else {
                    $options = [];
                    for ($i = 1; $i < ($set_karma['karma_points'] - $sum + 1); $i++) {
                        $options[] = $i;
                    }
                    $data['options'] = $options;
                    $data['vote_title'] = __('Vote for') . ': ' . $tools->checkout($user_data['name']);
                    $data['form_action'] = '?act=karma&amp;mod=vote&amp;user=' . $user_data['id'];
                    $data['back_url'] = '?user=' . $user_data['id'];
                    echo $view->render(
                        'profile::karma_vote',
                        [
                            'title'      => $title,
                            'page_title' => $title,
                            'data'       => $data,
                        ]
                    );
                }
            } else {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-danger',
                        'message'       => __('You are not allowed to vote for users'),
                        'back_url'      => '?user=' . $user_data['id'],
                        'back_url_name' => __('Back'),
                    ]
                );
            }
            break;

        case 'delete':
            // Удаляем отдельный голос
            if ($user->rights === 9) {
                $type = isset($_GET['type']) ? (int) $_GET['type'] : null;
                $req = $db->query("SELECT * FROM `karma_users` WHERE `id` = '${id}' AND `karma_user` = '" . $user_data['id'] . "'");

                if ($req->rowCount()) {
                    $res = $req->fetch();

                    if (
                        isset($post['delete_token'], $_SESSION['delete_token']) &&
                        $_SESSION['delete_token'] === $post['delete_token'] &&
                        $request->getMethod() === 'POST'
                    ) {
                        $db->exec("DELETE FROM `karma_users` WHERE `id` = '${id}'");
                        if ($res['type']) {
                            $sql = "`karma_plus` = '" . ($user_data['karma_plus'] > $res['points'] ? $user_data['karma_plus'] - $res['points'] : 0) . "'";
                        } else {
                            $sql = "`karma_minus` = '" . ($user_data['karma_minus'] > $res['points'] ? $user_data['karma_minus'] - $res['points'] : 0) . "'";
                        }

                        $db->exec("UPDATE `users` SET ${sql} WHERE `id` = " . $user_data['id']);
                        header('Location: ?act=karma&user=' . $user_data['id'] . '&type=' . $type);
                    } else {
                        $delete_token = uniqid('', true);
                        $_SESSION['delete_token'] = $delete_token;
                        $data['delete_token'] = $delete_token;
                        $data['form_action'] = '?act=karma&amp;mod=delete&amp;user=' . $user_data['id'] . '&amp;id=' . $id . '&amp;type=' . $type . '&amp;yes';
                        $data['message'] = __('Do you really want to delete comment?');
                        $data['back_url'] = '?act=karma&amp;user=' . $user_data['id'] . '&amp;type=' . $type;

                        echo $view->render(
                            'profile::karma_delete',
                            [
                                'title'      => $title,
                                'page_title' => $title,
                                'data'       => $data,
                            ]
                        );
                    }
                }
            }
            break;

        case 'clean':
            // Очищаем все голоса за пользователя
            if ($user->rights === 9) {
                if (
                    isset($post['delete_token'], $_SESSION['delete_token']) &&
                    $_SESSION['delete_token'] === $post['delete_token'] &&
                    $request->getMethod() === 'POST'
                ) {
                    $db->exec('DELETE FROM `karma_users` WHERE `karma_user` = ' . $user_data['id']);
                    $db->query('OPTIMIZE TABLE `karma_users`');
                    $db->exec("UPDATE `users` SET `karma_plus` = '0', `karma_minus` = '0' WHERE `id` = " . $user_data['id']);
                    header('Location: ?user=' . $user_data['id']);
                } else {
                    $delete_token = uniqid('', true);
                    $_SESSION['delete_token'] = $delete_token;
                    $data['delete_token'] = $delete_token;
                    $data['form_action'] = '?act=karma&amp;mod=clean&amp;user=' . $user_data['id'] . '&amp;yes';
                    $data['message'] = __('Do you really want to delete all reviews about user?');
                    $data['back_url'] = '?act=karma&amp;user=' . $user_data['id'];

                    echo $view->render(
                        'profile::karma_delete',
                        [
                            'title'      => $title,
                            'page_title' => $title,
                            'data'       => $data,
                        ]
                    );
                }
            }

            break;

        case 'new':
            // Список новых отзывов (комментариев)
            $title = __('New responses');
            $total = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '" . $user->id . "' AND `time` > " . (time() - 86400))->fetchColumn();

            if ($total) {
                $req = $db->query("SELECT * FROM `karma_users` WHERE `karma_user` = '" . $user->id . "' AND `time` > " . (time() - 86400) . " ORDER BY `time` DESC LIMIT ${start}, " . $user->config->kmess);
                $items = [];
                while ($res = $req->fetch()) {
                    $res['text'] = $tools->smilies($tools->checkout($res['text']));
                    $res['display_date'] = $tools->displayDate($res['time']);
                    $items[] = $res;
                }
            }

            $data['back_url'] = '?user=' . $user_data['id'];
            $data['total'] = $total;
            $data['filters'] = [];
            $data['pagination'] = $tools->displayPagination('?act=karma&amp;mod=new&amp;', $start, $total, $user->config->kmess);
            $data['items'] = $items ?? [];

            echo $view->render(
                'profile::karma',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
            break;

        default:
            // Главная страница Кармы, список отзывов
            $type = isset($_GET['type']) ? (int) ($_GET['type']) : 0;

            $data['filters'] = [
                'all'      => [
                    'name'   => __('All'),
                    'url'    => '?act=karma&amp;user=' . $user_data['id'] . '&amp;type=2',
                    'active' => $type === 2,
                ],
                'positive' => [
                    'name'   => __('Positive'),
                    'url'    => '?act=karma&amp;user=' . $user_data['id'] . '&amp;type=1',
                    'active' => $type === 1,
                ],
                'negative' => [
                    'name'   => __('Negative'),
                    'url'    => '?act=karma&amp;user=' . $user_data['id'],
                    'active' => ! $type,
                ],
            ];

            $items = [];
            $total = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '" . $user_data['id'] . "'" . ($type == 2 ? '' : " AND `type` = '${type}'"))->fetchColumn();
            if ($total) {
                $req = $db->query("SELECT * FROM `karma_users` WHERE `karma_user` = '" . $user_data['id'] . "'" . ($type == 2 ? '' : " AND `type` = '${type}'") . " ORDER BY `time` DESC LIMIT ${start}, " . $user->config->kmess);
                while ($res = $req->fetch()) {
                    $res['text'] = $tools->smilies($tools->checkout($res['text']));
                    $res['display_date'] = $tools->displayDate($res['time']);
                    if ($user->rights === 9) {
                        $res['delete_url'] = '?act=karma&amp;mod=delete&amp;user=' . $user_data['id'] . '&amp;id=' . $res['id'] . '&amp;type=' . $type;
                    }
                    $items[] = $res;
                }
            }

            if ($user->rights === 9) {
                $data['reset_url'] = '?act=karma&amp;user=' . $user_data['id'] . '&amp;mod=clean';
            }
            $data['back_url'] = '?user=' . $user_data['id'];

            $data['total'] = $total;
            $data['pagination'] = $tools->displayPagination('?act=karma&amp;user=' . $user_data['id'] . '&amp;type=' . $type . '&amp;', $start, $total, $user->config->kmess);
            $data['items'] = $items ?? [];

            echo $view->render(
                'profile::karma',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
    }
} else {
    pageNotFound();
}
