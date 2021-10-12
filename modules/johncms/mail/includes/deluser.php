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

$title = __('Delete');
$nav_chain->add($title);

if ($id) {
    if (isset($_POST['submit'])) {
        $req = $db->query('SELECT * FROM `cms_mail` WHERE ((`user_id` = ' . $id . ' AND `from_id` = ' . $user->id . ') OR (`user_id` = ' . $user->id . ' AND `from_id` = ' . $id . ')) AND `delete` != ' . $user->id);

        while ($row = $req->fetch()) {
            //Удаляем сообщения
            if ($row['delete'] > 0 || ($row['read'] === 0 && $row['user_id'] === $user->id)) {
                //Удаляем файлы
                if (! empty($row['file_name']) && file_exists(UPLOAD_PATH . 'mail/' . $row['file_name'])) {
                    @unlink(UPLOAD_PATH . 'mail/' . $row['file_name']);
                }

                $db->exec('DELETE FROM `cms_mail` WHERE `id` = ' . $row['id']);
            } else {
                $db->exec('UPDATE `cms_mail` SET `delete` = ' . $user->id . ' WHERE `id` = ' . $row['id']);
            }
        }

        //Удаляем контакт
        $db->exec('DELETE FROM `cms_contact` WHERE `user_id` = ' . $user->id . ' AND `from_id` = ' . $id . ' LIMIT 1');
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-success',
                'message'       => __('Contact deleted'),
                'back_url'      => './',
                'back_url_name' => __('Back'),
            ]
        );
    } else {
        $data = [
            'form_action'     => '?act=deluser&amp;id=' . $id,
            'message'         => __('When you delete a contact is deleted and all correspondence with him.<br>Are you sure you want to delete?'),
            'back_url'        => (isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : './'),
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
            'message'  => __('Contact for removal isn\'t chosen'),
            'back_url' => '../personal/',
        ]
    );
}
