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

$title = __('Blacklist');
$nav_chain->add($title);

if (isset($_GET['del'])) {
    if ($id) {
        //Проверяем существование пользователя
        $req = $db->query('SELECT * FROM `users` WHERE `id` = ' . $id);

        if (! $req->rowCount()) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'   => $title,
                    'type'    => 'alert-danger',
                    'message' => __('User does not exists'),
                ]
            );
            exit;
        }

        //Удаляем из заблокированных
        if (isset($_POST['submit'])) {
            $q = $db->query("SELECT * FROM `cms_contact` WHERE `user_id`='" . $user->id . "' AND `from_id`='" . $id . "' AND `ban`='1'");

            if (! $q->rowCount()) {
                $message = __('User not blocked');
            } else {
                $db->exec("UPDATE `cms_contact` SET `ban`='0' WHERE `user_id`='" . $user->id . "' AND `from_id`='${id}' AND `ban`='1'");
                $message = __('User is unblocked');
            }
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-success',
                    'message'       => $message,
                    'back_url'      => './',
                    'back_url_name' => __('Continue'),
                ]
            );
        } else {
            $data = [
                'form_action'     => '?act=ignor&amp;id=' . $id . '&amp;del',
                'message'         => __('You really want to unblock contact?'),
                'back_url'        => '/profile/?user=' . $id,
                'submit_btn_name' => __('Unblock'),
            ];
            echo $view->render(
                'mail::confirm',
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
                'title'   => $title,
                'type'    => 'alert-danger',
                'message' => __('Contact isn\'t chosen'),
            ]
        );
    }
} elseif (isset($_GET['add'])) {
    if ($id) {
        $req = $db->query('SELECT * FROM `users` WHERE `id` = ' . $id);

        if (! $req->rowCount()) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'   => $title,
                    'type'    => 'alert-danger',
                    'message' => __('User does not exists'),
                ]
            );
            exit;
        }

        $res = $req->fetch();

        //Добавляем в заблокированные
        if (isset($_POST['submit'])) {
            if ($res['rights'] > $user->rights) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-danger',
                        'message'       => __('This user can not be blocked'),
                        'back_url'      => './',
                        'back_url_name' => __('Continue'),
                    ]
                );
            } else {
                $q = $db->query(
                    "SELECT * FROM `cms_contact`
				WHERE `user_id`='" . $user->id . "' AND `from_id`='" . $id . "';"
                );

                if (! $q->rowCount()) {
                    $db->query(
                        "INSERT INTO `cms_contact` SET
					`user_id` = '" . $user->id . "',
					`from_id` = '" . $id . "',
					`time` = '" . time() . "',
					`ban`='1'"
                    );
                } else {
                    $db->exec("UPDATE `cms_contact` SET `ban`='1', `friends`='0', `type`='1' WHERE `user_id`='" . $user->id . "' AND `from_id`='${id}'");
                    $db->exec("UPDATE `cms_contact` SET `friends`='0', `type`='1' WHERE `user_id`='${id}' AND `from_id`='" . $user->id . "'");
                }

                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-success',
                        'message'       => __('User is blocked'),
                        'back_url'      => './',
                        'back_url_name' => __('Continue'),
                    ]
                );
            }
        } else {
            $data = [
                'form_action'     => '?act=ignor&amp;id=' . $id . '&amp;add',
                'message'         => __('You really want to block contact?'),
                'back_url'        => (isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : './'),
                'submit_btn_name' => __('Block'),
            ];
            echo $view->render(
                'mail::confirm',
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
                'message'       => __('Contact isn\'t chosen'),
                'back_url'      => './',
                'back_url_name' => __('Continue'),
            ]
        );
    }
} else {
    $data = [];
    $data['filters'] = [
        'all'      => [
            'name'   => __('My Contacts'),
            'url'    => '/mail/',
            'active' => false,
        ],
        'positive' => [
            'name'   => __('Blocklist'),
            'url'    => '?act=ignor',
            'active' => true,
        ],
    ];

    //Отображаем список заблокированных контактов
    $total = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id` = '" . $user->id . "' AND `ban`='1'")->fetchColumn();

    if ($total) {
        $req = $db->query(
            "SELECT `users`.* FROM `cms_contact`
		    LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id`
		    WHERE `cms_contact`.`user_id`='" . $user->id . "'
		    AND `ban`='1'
		    ORDER BY `cms_contact`.`time` DESC
		    LIMIT ${start}, " . $user->config->kmess
        );

        $items = [];
        while ($row = $req->fetch()) {
            $count_message = $db->query(
                "SELECT COUNT(*) FROM `cms_mail`
                WHERE ((`user_id`='{$row['id']}' AND `from_id`='" . $user->id . "') OR (`user_id`='" . $user->id . "' AND `from_id`='{$row['id']}'))
                AND `delete`!='" . $user->id . "' AND `sys`!='1' AND `spam`!='1'"
            )->fetchColumn();
            $new_count_message = $db->query(
                "SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`user_id`='" . $user->id . "' AND `cms_mail`.`from_id`='{$row['id']}' AND `read`='0' AND `delete`!='" . $user->id . "' AND `sys`!='1' AND `spam`!='1'"
            )->fetchColumn();

            $row['count_message'] = $count_message;
            $row['new_count_message'] = $new_count_message;
            $row['user_is_online'] = time() <= $row['lastdate'] + 300;

            $row['buttons'] = [
                [
                    'url'  => '?act=write&amp;id=' . $row['id'],
                    'name' => __('Correspondence'),
                ],
                [
                    'url'  => '?act=deluser&amp;id=' . $row['id'],
                    'name' => __('Delete'),
                ],
                [
                    'url'  => '?act=ignor&amp;id=' . $row['id'] . '&amp;del',
                    'name' => __('Unblock'),
                ],
            ];

            $items[] = $row;
        }
    }

    $data['back_url'] = '../profile/?act=office';

    $data['total'] = $total;
    $data['pagination'] = $tools->displayPagination('?act=ignor&amp;', $start, $total, $user->config->kmess);
    $data['items'] = $items ?? [];

    echo $view->render(
        'mail::contact_list',
        [
            'title'      => $title,
            'page_title' => $title,
            'data'       => $data,
        ]
    );
}
