<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

if (! $user->isValid()) {
    echo $view->render('system::app/old_content', [
        'title'   => $textl,
        'content' => $tools->displayError(_t('Access forbidden')),
    ]);
    exit;
}

// Проверяем наличие комментируемого объекта
$req_obj = $db->query('SELECT * FROM `library_texts` WHERE `id`=' . $id);

if ($req_obj->rowCount()) {
    $res_obj = $req_obj->fetch();

    if (! $res_obj) {
        echo $view->render('system::app/old_content', [
            'title'   => $textl,
            'content' => $tools->displayError(_t('Access forbidden')),
        ]);
        exit;
    }

    $obj = new Library\Hashtags($id);
    $catalog = $db->query('SELECT `id`, `name` FROM `library_cats` WHERE `id`=' . $res_obj['cat_id'] . ' LIMIT 1')->fetch();
    $context_top =
        '<div class="phdr"><a href="?"><strong>' . _t('Library') . '</strong></a> | <a href="?do=dir&amp;id=' . $catalog['id'] . '">' . $tools->checkout($catalog['name']) . '</a></div>' .
        '<div class="menu">' .
        '<p><b><a href="?id=' . $id . '">' . $tools->checkout($res_obj['name']) . '</a></b></p>' .
        '<small>' . $tools->smilies($tools->checkout($res_obj['announce'], 1, 1)) . '</small>' .
        '<div class="sub">' .
        ($obj->getAllStatTags() ? '<span class="gray">' . _t('Tags') . ':</span> [ ' . $obj->getAllStatTags(1) . ' ]<br>' : '') .
        '<span class="gray">' . _t('Who added') . ':</span> <a href="' . $config['homeurl'] . '/profile/?user=' . $res_obj['uploader_id'] . '">' . $tools->checkout($res_obj['uploader']) . '</a> (' . $tools->displayDate($res_obj['time']) . ')<br>' .
        '<span class="gray">' . _t('Number of readings') . ':</span> ' . $res_obj['count_views'] .
        '</div></div>';
    $arg = [
        'comments_table' => 'cms_library_comments',
        // Таблица с комментариями
        'object_table' => 'library_texts',
        // Таблица комментируемых объектов
        'script' => '?act=comments',
        // Имя скрипта (с параметрами вызова)
        'sub_id_name' => 'id',
        // Имя идентификатора комментируемого объекта
        'sub_id' => $id,
        // Идентификатор комментируемого объекта
        'owner' => $res_obj['uploader_id'],
        // Владелец объекта (ID того юзера, который может управлять каментами, если разрешено ниже)
        'owner_delete' => true,
        // Возможность владельцу удалять комментарий
        'owner_reply' => true,
        // Возможность владельцу отвечать на комментарий
        'owner_edit' => false,
        // Возможность владельцу редактировать комментарий
        'title' => _t('Comments'),
        // Название раздела
        'context_top' => $context_top,
        // Выводится вверху списка
    ];
    $comm = new Johncms\Utility\Comments($arg);

    if ($comm->added) {
        $db->exec('UPDATE `library_texts` SET `comm_count`=' . ($res_obj['comm_count'] > 0 ? ++$res_obj['comm_count'] : 1) . ' WHERE `id`=' . $id);
    }
} else {
    echo $tools->displayError(_t('Wrong data'));
}
