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

$title = __('Deleting messages');

if ($id) {
    //Проверяем наличие сообщения
    $req = $db->query("SELECT * FROM `cms_mail` WHERE (`user_id`='" . $user->id . "' OR `from_id`='" . $user->id . "') AND `id` = '${id}' AND `delete`!='" . $user->id . "' LIMIT 1");

    if (! $req->rowCount()) {
        //Выводим ошибку
        echo $view->render(
            'system::pages/result',
            [
                'title'   => $title,
                'type'    => 'alert-danger',
                'message' => __('Message does not exist'),
            ]
        );
        exit;
    }

    $res = $req->fetch();

    if (isset($_POST['submit'])) { //Если кнопка "Подвердить" нажата
        //Удаляем системное сообщение
        if ($res['sys']) {
            $db->exec("DELETE FROM `cms_mail` WHERE `from_id`='" . $user->id . "' AND `id` = '${id}' AND `sys`='1' LIMIT 1");
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-success',
                    'message'       => __('Message deleted'),
                    'back_url'      => '?act=systems',
                    'back_url_name' => __('Back'),
                ]
            );
        } else {
            //Удаляем непрочитанное сообщение
            if ($res['read'] == 0 && $res['user_id'] == $user->id) {
                //Удаляем файл
                if ($res['file_name']) {
                    @unlink(UPLOAD_PATH . 'mail/' . $res['file_name']);
                }

                $db->exec("DELETE FROM `cms_mail` WHERE `user_id`='" . $user->id . "' AND `id` = '${id}' LIMIT 1");
            } elseif ($res['delete']) {
                //Удаляем файл
                if ($res['file_name']) {
                    @unlink(UPLOAD_PATH . 'mail/' . $res['file_name']);
                }

                $db->exec("DELETE FROM `cms_mail` WHERE (`user_id`='" . $user->id . "' OR `from_id`='" . $user->id . "') AND `id` = '${id}' LIMIT 1");
            } else {
                $db->exec("UPDATE `cms_mail` SET `delete` = '" . $user->id . "' WHERE `id` = '${id}' LIMIT 1");
            }
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-success',
                    'message'       => __('Message deleted'),
                    'back_url'      => '?act=write&amp;id=' . ($res['user_id'] === $user->id ? $res['from_id'] : $res['user_id']),
                    'back_url_name' => __('Back'),
                ]
            );
        }
    } else {
        $data = [
            'form_action'     => '?act=delete&amp;id=' . $id,
            'message'         => __('You really want to remove the message?'),
            'back_url'        => '?act=write&amp;id=' . ($res['user_id'] === $user->id ? $res['from_id'] : $res['user_id']),
            'submit_btn_name' => __('Delete'),
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
            'title'    => $title,
            'type'     => 'alert-danger',
            'message'  => __('The message for removal isn\'t chosen'),
            'back_url' => '../personal/',
        ]
    );
}
