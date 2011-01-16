<?php

// Проверяем наличие комментируемого объекта
$req = mysql_query("SELECT * FROM `cms_album_files` WHERE `id` = '$img'");
if (mysql_num_rows($req)) {
    $res = mysql_fetch_assoc($req);

    /*
    -----------------------------------------------------------------
    Получаем данные владельца Альбома
    -----------------------------------------------------------------
    */
    $owner = functions::get_user($res['user_id']);
    if (!$owner) {
        echo functions::display_error($lng['user_does_not_exist']);
        require('../incfiles/end.php');
        exit;
    }

    /*
    -----------------------------------------------------------------
    Показываем выбранную картинку
    -----------------------------------------------------------------
    */
    unset($_SESSION['ref']);
    $req_a = mysql_query("SELECT * FROM `cms_album_cat` WHERE `id` = '" . $res['album_id'] . "'");
    $res_a = mysql_fetch_assoc($req_a);
    if ($res_a['access'] == 1 && $owner['id'] != $user_id && $rights < 6) {
        // Если доступ закрыт
        echo functions::display_error($lng['access_forbidden']) .
            '<div class="phdr"><a href="album.php?act=list&amp;user=' . $owner['id'] . '">' . $lng_profile['album_list'] . '</a></div>';
        require('../incfiles/end.php');
        exit;
    }
    $context_top = '<div class="phdr"><a href="album.php"><b>' . $lng['photo_albums'] . '</b></a> | ' .
        '<a href="album.php?act=list&amp;user=' . $owner['id'] . '">' . $lng['personal_2'] . '</a></div>' .
        '<div class="menu"><a href="album.php?act=show&amp;al=' . $res['album_id'] . '&amp;img=' . $img . '&amp;user=' . $owner['id'] . '&amp;view"><img src="../files/users/album/' . $owner['id'] . '/' . $res['tmb_name'] . '" /></a>';
    if (!empty($res['description']))
        $context_top .= '<div class="gray">' . functions::smileys(functions::checkout($res['description'], 1)) . '</div>';
    $context_top .= '<div class="sub">' .
        '<a href="profile.php?user=' . $owner['id'] . '"><b>' . $owner['name'] . '</b></a> | ' .
        '<a href="album.php?act=show&amp;al=' . $res_a['id'] . '&amp;user=' . $owner['id'] . '">' . functions::checkout($res_a['name']) . '</a>';
    if ($res['access'] == 4 || $rights >= 7) {
        $context_top .= vote_photo($res) .
            '<div class="gray">' . $lng['count_views'] . ': ' . $res['views'] . ', ' . $lng['count_downloads'] . ': ' . $res['downloads'] . '</div>' .
            '<a href="album.php?act=image_download&amp;img=' . $res['id'] . '">' . $lng['download'] . '</a>';
    }
    $context_top .= '</div></div>';

    /*
    -----------------------------------------------------------------
    Параметры комментариев
    -----------------------------------------------------------------
    */
    $arg = array (
        'comments_table' => 'cms_album_comments', // Таблица с комментариями
        'object_table' => 'cms_album_files',      // Таблица комментируемых объектов
        'script' => 'album.php?act=comments',     // Имя скрипта (с параметрами вызова)
        'sub_id_name' => 'img',                   // Имя идентификатора комментируемого объекта
        'sub_id' => $img,                         // Идентификатор комментируемого объекта
        'owner' => $owner['id'],                  // Владелец объекта
        'owner_delete' => true,                   // Возможность владельцу удалять комментарий
        'owner_reply' => true,                    // Возможность владельцу отвечать на комментарий
        'owner_edit' => false,                    // Возможность владельцу редактировать комментарий
        'title' => $lng['comments'],              // Название раздела
        'context_top' => $context_top,            // Выводится вверху списка
        'context_bottom' => ''                    // Выводится внизу списка
    );

    /*
    -----------------------------------------------------------------
    Показываем комментарии
    -----------------------------------------------------------------
    */
    $comm = new comments($arg);
} else {
    echo functions::display_error($lng['error_wrong_data']);
}
?>