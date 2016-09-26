<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';
$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;

// Комментарии
//TODO: Переделать на получение настроек из таблицы модулей
if (!App::cfg()->sys->acl_downloads_comm && $rights < 7) {
    echo $lng['comments_cloded'] . ' <a href="' . $url . '">' . _t('Downloads') . '</a>';
    exit;
}

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || ($res_down['type'] == 3 && $rights < 6 && $rights != 4)) {
    echo $lng['not_found_file'] . ' <a href="' . $url . '">' . _t('Downloads') . '</a>';
    exit;
}

//TODO: Переделать на получение настроек из таблицы модулей
if (!App::cfg()->sys->acl_downloads_comm) {
    echo '<div class="rmenu">' . $lng['comments_cloded'] . '</div>';
}

$title_pages = htmlspecialchars(mb_substr($res_down['rus_name'], 0, 30));
$textl = $lng['comments'] . ': ' . (mb_strlen($res_down['rus_name']) > 30 ? $title_pages . '...' : $title_pages);

// Параметры комментариев
$arg = [
    'object_comm_count' => 'total', // Поле с числом комментариев
    'comments_table'    => 'cms_download_comments', // Таблица с комментариями
    'object_table'      => 'cms_download_files', // Таблица комментируемых объектов
    'script'            => $url . '?act=comments', // Имя скрипта (с параметрами вызова)
    'sub_id_name'       => 'id', // Имя идентификатора комментируемого объекта
    'sub_id'            => $id, // Идентификатор комментируемого объекта
    'owner'             => false, // Владелец объекта
    'owner_delete'      => false, // Возможность владельцу удалять комментарий
    'owner_reply'       => false, // Возможность владельцу отвечать на комментарий
    'owner_edit'        => false, // Возможность владельцу редактировать комментарий
    'title'             => $lng['comments'], // Название раздела
    'context_top'       => '<div class="phdr"><b>' . $textl . '</b></div>', // Выводится вверху списка
    'context_bottom'    => '<p><a href="' . $url . '?act=view&amp;id=' . $id . '">' . $lng['back'] . '</a></p>' // Выводится внизу списка
];

// Показываем комментарии
$comm = new comments($arg);
