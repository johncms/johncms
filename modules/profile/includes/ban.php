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

$ban = isset($_GET['ban']) ? (int) ($_GET['ban']) : 0;
$title = __('Ban the User');
$set_karma = $config['karma'];
$data = [];

switch ($mod) {
    case 'do':
        // Баним пользователя (добавляем Бан в базу)
        $nav_chain->add($title);
        if ($user->rights < 1 || ($user->rights < 6 && $user_data['rights']) || ($user->rights <= $user_data['rights'])) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'    => $title,
                    'type'     => 'alert-danger',
                    'message'  => __('You do not have enought rights to ban this user'),
                    'back_url' => '?user=' . $user_data['id'],
                ]
            );
        } else {
            if (isset($_POST['submit'])) {
                $error = false;
                $term = isset($_POST['term']) ? (int) ($_POST['term']) : false;
                $timeval = isset($_POST['timeval']) ? (int) ($_POST['timeval']) : false;
                $time = isset($_POST['time']) ? (int) ($_POST['time']) : false;
                $reason = ! empty($_POST['reason']) ? trim($_POST['reason']) : '';
                $banref = isset($_POST['banref']) ? (int) ($_POST['banref']) : false;

                if (empty($reason) && empty($banref)) {
                    $reason = __('Reason not specified');
                }

                if (empty($term) || empty($timeval) || empty($time) || $timeval < 1) {
                    $error = __('There is no required data');
                }

                if (
                    ($user->rights === 1 && $term !== 14) ||
                    ($user->rights === 2 && $term !== 12) ||
                    ($user->rights === 3 && $term !== 11) ||
                    ($user->rights === 4 && $term !== 16) ||
                    ($user->rights === 5 && $term !== 15)
                ) {
                    $error = __('You have no rights to ban in this section');
                }

                if ($db->query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `user_id` = '" . $user_data['id'] . "' AND `ban_time` > '" . time() . "' AND `ban_type` = '${term}'")->fetchColumn()) {
                    $error = __('Ban already active');
                }

                switch ($time) {
                    case 2:
                        // Часы
                        if ($timeval > 24) {
                            $timeval = 24;
                        }
                        $timeval = $timeval * 3600;
                        break;

                    case 3:
                        // Дни
                        if ($timeval > 30) {
                            $timeval = 30;
                        }
                        $timeval = $timeval * 86400;
                        break;

                    case 4:
                        // До отмены (на 10 лет)
                        $timeval = 315360000;
                        break;

                    default:
                        // Минуты
                        if ($timeval > 60) {
                            $timeval = 60;
                        }
                        $timeval = $timeval * 60;
                }

                if ($user->rights < 6 && $timeval > 86400) {
                    $timeval = 86400;
                }

                if ($user->rights < 7 && $timeval > 2592000) {
                    $timeval = 2592000;
                }

                if (! $error) {
                    // Заносим в базу
                    $stmt = $db->prepare(
                        'INSERT INTO `cms_ban_users` SET
                      `user_id` = ?,
                      `ban_time` = ?,
                      `ban_while` = ?,
                      `ban_type` = ?,
                      `ban_who` = ?,
                      `ban_reason` = ?
                    '
                    );

                    $stmt->execute(
                        [
                            $user_data['id'],
                            (time() + $timeval),
                            time(),
                            $term,
                            $user->name,
                            $reason,
                        ]
                    );

                    if ($set_karma['on']) {
                        $points = $set_karma['karma_points'] * 2;
                        $stmt = $db->prepare(
                            'INSERT INTO `karma_users` SET
                          `user_id` = 0,
                          `name` = ?,
                          `karma_user` = ?,
                          `points` = ?,
                          `type` = 0,
                          `time` = ?,
                          `text` = ?
                        '
                        );

                        $stmt->execute(
                            [
                                __('System'),
                                $user_data['id'],
                                $points,
                                time(),
                                __('Ban'),
                            ]
                        );

                        $db->exec('UPDATE `users` SET `karma_minus` = ' . (int) ($user_data['karma_minus'] + $points) . ' WHERE `id` = ' . $user_data['id']);
                    }
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'    => $title,
                            'type'     => 'alert-success',
                            'message'  => __('User banned'),
                            'back_url' => '?user=' . $user_data['id'],
                        ]
                    );
                } else {
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'    => $title,
                            'type'     => 'alert-danger',
                            'message'  => $error,
                            'back_url' => '?user=' . $user_data['id'],
                        ]
                    );
                }
            } else {
                $data['form_action'] = '?act=ban&amp;mod=do&amp;user=' . $user_data['id'];
                $data['post_id'] = $request->getQuery('fid', 0, FILTER_VALIDATE_INT);
                $data['back_url'] = '?user=' . $user_data['id'];
                $data['user_login'] = $user_data['name'];
                echo $view->render(
                    'profile::ban',
                    [
                        'title'      => $title,
                        'page_title' => $title,
                        'data'       => $data,
                    ]
                );
            }
        }
        break;

    case 'cancel':
        // Разбаниваем пользователя (с сохранением истории)
        $title = __('Ban termination');
        $nav_chain->add($title);
        if (! $ban || $user_data['id'] == $user->id || $user->rights < 7) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'    => $title,
                    'type'     => 'alert-danger',
                    'message'  => __('Wrong data'),
                    'back_url' => '?user=' . $user_data['id'],
                ]
            );
        } else {
            $req = $db->query("SELECT * FROM `cms_ban_users` WHERE `id` = '${ban}' AND `user_id` = " . $user_data['id']);

            if ($req->rowCount()) {
                $res = $req->fetch();
                $error = false;

                if ($res['ban_time'] < time()) {
                    $error = __('Ban not active');
                }

                if (! $error) {
                    if (isset($_POST['submit'])) {
                        $db->exec("UPDATE `cms_ban_users` SET `ban_time` = '" . time() . "' WHERE `id` = '${ban}'");
                        echo $view->render(
                            'system::pages/result',
                            [
                                'title'    => $title,
                                'type'     => 'alert-success',
                                'message'  => __('Ban terminated'),
                                'back_url' => '?act=ban&amp;user=' . $user_data['id'],
                            ]
                        );
                    } else {
                        $data['message'] = __('Ban time is going to the end. Infrigement will be saved in the bans history');
                        $data['submit_name'] = __('Terminate Ban');
                        $data['form_action'] = '?act=ban&amp;mod=cancel&amp;user=' . $user_data['id'] . '&amp;ban=' . $ban;
                        $data['back_url'] = '?user=' . $user_data['id'];
                        echo $view->render(
                            'profile::ban_cancel',
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
                            'title'    => $title,
                            'type'     => 'alert-danger',
                            'message'  => $error,
                            'back_url' => '?user=' . $user_data['id'],
                        ]
                    );
                }
            } else {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'    => $title,
                        'type'     => 'alert-danger',
                        'message'  => __('Wrong data'),
                        'back_url' => '?user=' . $user_data['id'],
                    ]
                );
            }
        }
        break;

    case 'delete':
        // Удаляем бан (с удалением записи из истории)
        $title = __('Delete ban');
        $nav_chain->add($title);

        if (! $ban || $user->rights < 9) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'    => $title,
                    'type'     => 'alert-danger',
                    'message'  => __('Wrong data'),
                    'back_url' => '?user=' . $user_data['id'],
                ]
            );
        } else {
            $req = $db->query("SELECT * FROM `cms_ban_users` WHERE `id` = '${ban}' AND `user_id` = " . $user_data['id']);

            if ($req->rowCount()) {
                $res = $req->fetch();
                if (isset($_POST['submit'])) {
                    $db->exec("DELETE FROM `karma_users` WHERE `karma_user` = '" . $user_data['id'] . "' AND `user_id` = '0' AND `time` = '" . $res['ban_while'] . "' LIMIT 1");
                    $points = $set_karma['karma_points'] * 2;
                    $db->exec(
                        "UPDATE `users` SET
                        `karma_minus` = '" . ($user_data['karma_minus'] > $points ? $user_data['karma_minus'] - $points : 0) . "'
                        WHERE `id` = " . $user_data['id']
                    );
                    $db->exec("DELETE FROM `cms_ban_users` WHERE `id` = '${ban}'");
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'    => $title,
                            'type'     => 'alert-success',
                            'message'  => __('Ban deleted'),
                            'back_url' => '?act=ban&amp;user=' . $user_data['id'],
                        ]
                    );
                } else {
                    $data['message'] = __('Removing ban along with a record in the bans history');
                    $data['submit_name'] = __('Delete');
                    $data['form_action'] = '?act=ban&amp;mod=delete&amp;user=' . $user_data['id'] . '&amp;ban=' . $ban;
                    $data['back_url'] = '?act=ban&amp;user=' . $user_data['id'];
                    echo $view->render(
                        'profile::ban_cancel',
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
                        'title'    => $title,
                        'type'     => 'alert-danger',
                        'message'  => __('Wrong data'),
                        'back_url' => '?user=' . $user_data['id'],
                    ]
                );
            }
        }
        break;

    case 'delhist':
        // Очищаем историю нарушений юзера
        $title = __('Violations history');
        $nav_chain->add($title);
        if ($user->rights === 9) {
            if (isset($_POST['submit'])) {
                $db->exec('DELETE FROM `cms_ban_users` WHERE `user_id` = ' . $user_data['id']);
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'    => $title,
                        'type'     => 'alert-success',
                        'message'  => __('Violations history cleared'),
                        'back_url' => '?act=ban&amp;user=' . $user_data['id'],
                    ]
                );
            } else {
                $data['message'] = __('Are you sure want to clean entire history of user violations?');
                $data['submit_name'] = __('Clear');
                $data['form_action'] = '?act=ban&amp;mod=delhist&amp;user=' . $user_data['id'];
                $data['back_url'] = '?act=ban&amp;user=' . $user_data['id'];
                echo $view->render(
                    'profile::ban_cancel',
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
                    'title'    => $title,
                    'type'     => 'alert-danger',
                    'message'  => __('Violations history can be cleared by Supervisor only'),
                    'back_url' => '?user=' . $user_data['id'],
                ]
            );
        }
        break;

    default:
        // История нарушений
        $title = __('Violations History');
        $nav_chain->add($title);
        if ($user->rights === 9) {
            $data['clear_history_url'] = '?act=ban&amp;mod=delhist&amp;user=' . $user_data['id'];
        }
        $data['user_name'] = $user_data['name'];
        $total = $db->query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `user_id` = '" . $user_data['id'] . "'")->fetchColumn();
        if ($total) {
            $req = $db->query("SELECT * FROM `cms_ban_users` WHERE `user_id` = '" . $user_data['id'] . "' ORDER BY `ban_time` DESC LIMIT ${start}, " . $user->set_user->kmess);
            $i = 0;

            $types = [
                1  => __('Full block'),
                2  => __('Private messages'),
                3  => __('Private messages'),
                10 => __('Comments'),
                11 => __('Forum'),
                13 => __('Guestbook'),
                15 => __('Library'),
            ];

            $items = [];
            while ($res = $req->fetch()) {
                $remain = $res['ban_time'] - time();
                $period = $res['ban_time'] - $res['ban_while'];
                $res['ban_type_name'] = $types[$res['ban_type']];
                $res['ban_started'] = date('d.m.Y / H:i', $res['ban_while']);
                $res['reason_formatted'] = $tools->checkout($res['ban_reason']);
                $res['time_name'] = ($period < 86400000 ? $tools->timecount($period) : __('Till cancel'));
                $res['remain'] = '';
                if ($remain > 0) {
                    $res['remain'] = $tools->timecount($remain);
                }

                // Меню отдельного бана
                $buttons = [];
                if ($user->rights >= 7 && $remain > 0) {
                    $buttons[] = [
                        'url'  => '?act=ban&amp;mod=cancel&amp;user=' . $user_data['id'] . '&amp;ban=' . $res['id'],
                        'name' => __('Cancel Ban'),
                    ];
                }
                if ($user->rights === 9) {
                    $buttons[] = [
                        'url'  => '?act=ban&amp;mod=delete&amp;user=' . $user_data['id'] . '&amp;ban=' . $res['id'],
                        'name' => __('Delete Ban'),
                    ];
                }
                $res['buttons'] = $buttons;

                $items[] = $res;
            }
        }

        $data['back_url'] = '?user=' . $user_data['id'];
        $data['total'] = $total;
        $data['pagination'] = $tools->displayPagination('?act=ban&amp;user=' . $user_data['id'] . '&amp;', $start, $total, $user->set_user->kmess);
        $data['items'] = $items ?? [];

        echo $view->render(
            'profile::ban_history',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
}
