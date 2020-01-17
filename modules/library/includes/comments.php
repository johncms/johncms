<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Library\Tree;

defined('_IN_JOHNCMS') || die('Error: restricted access');

if (! $user->isValid()) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => __('Access forbidden'),
        ]
    );
    exit;
}

// Проверяем наличие комментируемого объекта
$req_obj = $db->query('SELECT * FROM `library_texts` WHERE `id` = ' . $id);

if ($req_obj->rowCount()) {
    $res_obj = $req_obj->fetch();

    if (! $res_obj) {
        http_response_code(403);
        echo $view->render(
            'system::pages/result',
            [
                'title'   => $title,
                'type'    => 'alert-danger',
                'message' => __('Access forbidden'),
            ]
        );
        exit;
    }

    $dir_nav = new Tree($res_obj['cat_id']);
    $dir_nav->processNavPanel();
    $dir_nav->printNavPanel();
    $nav_chain->add($tools->checkout($res_obj['name']));

    $arg = [
        'comments_table' => 'cms_library_comments',
        // Таблица с комментариями
        'object_table'   => 'library_texts',
        // Таблица комментируемых объектов
        'script'         => '?act=comments',
        // Имя скрипта (с параметрами вызова)
        'sub_id_name'    => 'id',
        // Имя идентификатора комментируемого объекта
        'sub_id'         => $id,
        // Идентификатор комментируемого объекта
        'owner'          => $res_obj['uploader_id'],
        // Владелец объекта (ID того юзера, который может управлять каментами, если разрешено ниже)
        'owner_delete'   => true,
        // Возможность владельцу удалять комментарий
        'owner_reply'    => true,
        // Возможность владельцу отвечать на комментарий
        'owner_edit'     => false,
        // Возможность владельцу редактировать комментарий
        'title'          => __('Comments'),
    ];
    $comm = new Johncms\Comments($arg);

    if ($comm->added) {
        $db->exec('UPDATE `library_texts` SET `comm_count`=' . ($res_obj['comm_count'] > 0 ? ++$res_obj['comm_count'] : 1) . ' WHERE `id`=' . $id);
    }
} else {
    echo $tools->displayError(__('Wrong data'));
}
