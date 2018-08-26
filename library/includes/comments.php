<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2015 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

if (!$user_id) {
    echo functions::display_error($lng['access_guest_forbidden']);
    require_once('../incfiles/end.php');
    exit;
}

// Проверяем наличие комментируемого объекта
$stmt = $db->query("SELECT * FROM `library_texts` WHERE `id`=" . $id . " LIMIT 1");

if ($stmt->rowCount()) {
    $res_obj = $stmt->fetch();

    if (!$res_obj['comments']) {
        echo functions::display_error($lng['access_forbidden']);
        require('../incfiles/end.php');
        exit;
    }

    $obj = new Hashtags($id);
    $catalog = $db->query("SELECT `id`, `name` FROM `library_cats` WHERE `id`=" . $res_obj['cat_id'] . " LIMIT 1")->fetch();
    $context_top =
        '<div class="phdr"><a href="?"><strong>' . $lng['library'] . '</strong></a> | <a href="?do=dir&amp;id=' . $catalog['id'] . '">' . functions::checkout($catalog['name']) . '</a></div>' .
        '<div class="menu">' .
        '<p><b><a href="index.php?id=' . $id . '">' . functions::checkout($res_obj['name']) . '</a></b></p>' .
        '<small>' . functions::smileys(functions::checkout($res_obj['announce'], 1, 1)) . '</small>' .
        '<div class="sub">' .
        ($obj->get_all_stat_tags() ? '<span class="gray">' . $lng_lib['tags'] . ':</span> [ ' . $obj->get_all_stat_tags(1) . ' ]<br/>' : '') .
        '<span class="gray">' . $lng_lib['added'] . ':</span> <a href="' . core::$system_set['homeurl'] . '/users/profile.php?user=' . $res_obj['uploader_id'] . '">' . functions::checkout($res_obj['uploader']) . '</a> (' . functions::display_date($res_obj['time']) . ')<br/>' .
        '<span class="gray">' . $lng_lib['reads'] . ':</span> ' . $res_obj['count_views'] .
        '</div></div>';
    $arg = array(
        'comments_table' => 'cms_library_comments',  // Таблица с комментариями
        'object_table'   => 'library_texts',         // Таблица комментируемых объектов
        'script'         => '?act=comments',         // Имя скрипта (с параметрами вызова)
        'sub_id_name'    => 'id',                    // Имя идентификатора комментируемого объекта
        'sub_id'         => $id,                     // Идентификатор комментируемого объекта
        'owner'          => $res_obj['uploader_id'], // Владелец объекта (ID того юзера, который может управлять каментами, если разрешено ниже)
        'owner_delete'   => true,                    // Возможность владельцу удалять комментарий
        'owner_reply'    => true,                    // Возможность владельцу отвечать на комментарий
        'owner_edit'     => false,                   // Возможность владельцу редактировать комментарий
        'title'          => $lng['comments'],        // Название раздела
        'context_top'    => $context_top,            // Выводится вверху списка
    );
    $comm = new comments($arg);

    if ($comm->added) {
        $db->exec("UPDATE `library_texts` SET `count_comments`=" . ($res_obj['count_comments'] > 0 ? ++$res_obj['count_comments'] : 1) . " WHERE `id`=" . $id);
    }
} else {
    echo functions::display_error($lng['error_wrong_data']);
}