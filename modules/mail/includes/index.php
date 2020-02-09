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

$title = __('Contacts');

$nav_chain->add($title);

if ($id) {
    $req = $db->query("SELECT * FROM `users` WHERE `id` = '${id}'");

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

    if ($id === $user->id) {
        echo $view->render(
            'system::pages/result',
            [
                'title'   => $title,
                'type'    => 'alert-danger',
                'message' => __('You cannot add yourself as a contact'),
            ]
        );
        exit;
    }

    if (isset($_POST['submit'])) {
        $q = $db->query('SELECT * FROM `cms_contact` WHERE `user_id` = ' . $user->id . ' AND `from_id` = ' . $id);

        if (! $q->rowCount()) {
            $db->query(
                'INSERT INTO `cms_contact` SET
                `user_id` = ' . $user->id . ',
                `from_id` = ' . $id . ',
                `time` = ' . time()
            );
        }
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-success',
                'message'       => __('User has been added to your contact list'),
                'back_url'      => './',
                'back_url_name' => __('Continue'),
            ]
        );
    } else {
        $data = [
            'form_action'     => '?id=' . $id . '&amp;add',
            'message'         => __('You really want to add contact?'),
            'back_url'        => '/profile/?user=' . $id,
            'submit_btn_name' => __('Add'),
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
    $data = [];
    $data['filters'] = [
        'all'      => [
            'name'   => __('My Contacts'),
            'url'    => '/mail/',
            'active' => true,
        ],
        'positive' => [
            'name'   => __('Blocklist'),
            'url'    => '?act=ignor',
            'active' => false,
        ],
    ];

    //Получаем список контактов
    $total = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='" . $user->id . "' AND `ban`!='1'")->fetchColumn();

    if ($total) {
        $req = $db->query(
            "SELECT `users`.*, `cms_contact`.`from_id` AS `id`
                FROM `cms_contact`
			    LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id`
			    WHERE `cms_contact`.`user_id`='" . $user->id . "'
			    AND `cms_contact`.`ban`!='1'
			    ORDER BY `users`.`name` ASC
			    LIMIT ${start}, " . $user->config->kmess
        );

        $items = [];
        while ($row = $req->fetch()) {
            $count_message = $db->query(
                "SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='{$row['id']}' AND `from_id`='" . $user->id . "') OR (`user_id`='" . $user->id . "' AND `from_id`='{$row['id']}')) AND `sys`!='1' AND `spam`!='1' AND `delete`!='" . $user->id . "'" // phpcs:ignore
            )->fetchColumn();
            $new_count_message = $db->query(
                "SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`user_id`='{$row['id']}' AND `cms_mail`.`from_id`='" . $user->id . "' AND `read`='0' AND `sys`!='1' AND `spam`!='1' AND `delete`!='" . $user->id . "'"
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
                    'url'  => '?act=ignor&amp;id=' . $row['id'] . '&amp;add',
                    'name' => __('Block User'),
                ],
            ];

            $items[] = $row;
        }
    }

    $data['back_url'] = '../profile/?act=office';

    $data['total'] = $total;
    $data['pagination'] = $tools->displayPagination('?', $start, $total, $user->config->kmess);
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
