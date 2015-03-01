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

// Проверяем наличие комментируемого объекта
$req_obj = mysql_query("SELECT * FROM `library_texts` WHERE `id`=" . $id);

if (mysql_num_rows($req_obj)) {
    $res_obj = mysql_fetch_assoc($req_obj);

    if (!$res_obj['comments']) {
        echo functions::display_error($lng['access_forbidden']);
        require('../incfiles/end.php');
        exit;
    }

    $owner = functions::get_user($res_obj['uploader_id']);

    if (!$owner) {
        echo functions::display_error($lng['user_does_not_exist']);
        require('../incfiles/end.php');
        exit;
    }

    $obj = new Hashtags($id);
    $catalog = mysql_fetch_assoc(mysql_query("SELECT `id`, `name` FROM `library_cats` WHERE `id`=" . $res_obj['cat_id'] . " LIMIT 1"));
    $context_top =
        '<div class="phdr"><a href="?"><strong>' . $lng['library'] . '</strong></a> | <a href="?do=dir&amp;id=' . $catalog['id'] . '">' . functions::checkout($catalog['name']) . '</a></div>' .
        '<div class="menu">' .
        '<p><b><a href="?do=text&amp;id=' . $id . '">' . functions::checkout($res_obj['name']) . '</a></b></p>' .
        '<small>' . functions::smileys(functions::checkout($res_obj['announce'], 1, 1)) . '</small>' .
        '<div class="sub">' .
        ($obj->get_all_stat_tags() ? '<span class="gray">' . $lng_lib['tags'] . ':</span> [ ' . $obj->get_all_stat_tags(1) . ' ]<br/>' : '') .
        '<span class="gray">' . $lng_lib['added'] . ':</span> <a href="../users/profile.php?user=' . $owner['id'] . '"><b>' . functions::checkout($owner['name']) . '</b></a> (' . functions::display_date($res_obj['time']) . ')<br/>' .
        '<span class="gray">' . $lng_lib['reads'] . ':</span> ' . $res_obj['count_views'] .
        '</div></div>';
    $arg = array(
        'comments_table' => 'cms_library_comments', // Таблица с комментариями
        'object_table'   => 'library_texts',        // Таблица комментируемых объектов
        'script'         => '?act=comments',        // Имя скрипта (с параметрами вызова)
        'sub_id_name'    => 'id',                   // Имя идентификатора комментируемого объекта
        'sub_id'         => $id,                    // Идентификатор комментируемого объекта
        'owner'          => $owner['id'],           // Владелец объекта
        'owner_delete'   => true,                   // Возможность владельцу удалять комментарий
        'owner_reply'    => false,                  // Возможность владельцу отвечать на комментарий
        'owner_edit'     => false,                  // Возможность владельцу редактировать комментарий
        'title'          => $lng['comments'],       // Название раздела
        'context_top'    => $context_top,           // Выводится вверху списка
        // 'context_bottom' => ''                                                 // Выводится внизу списка
    );
    $comm = new comments($arg);

    if ($comm->added) {
        mysql_query("UPDATE `library_texts` SET `count_comments`=" . ($res_obj['count_comments'] > 0 ? ++$res_obj['count_comments'] : 1) . " WHERE `id`=" . $id);
    }
} else {
    echo functions::display_error($lng['error_wrong_data']);
}