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

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\Legacy\Tools $tools
 */

// Проверяем наличие комментируемого объекта
$req_obj = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '${img}'");

if ($req_obj->rowCount()) {
    $res_obj = $req_obj->fetch();

    // Получаем данные владельца Альбома
    $owner = $db->query('SELECT * FROM `users` WHERE `id` = ' . $res_obj['user_id'])->fetch();

    if (! $owner) {
        echo $view->render(
            'system::pages/result',
            [
                'title'    => $title,
                'type'     => 'alert-danger',
                'message'  => __('User does not exists'),
                'back_url' => './list?user=' . $owner['id'],
            ]
        );
        exit;
    }

    // Показываем выбранную картинку
    unset($_SESSION['ref']);
    $res_a = $db->query('SELECT * FROM `cms_album_cat` WHERE `id` = ' . $res_obj['album_id'])->fetch();

    $nav_chain->add(htmlspecialchars($res_a['name']));

    if (($res_a['access'] === 1 && $owner['id'] !== $user->id && $user->rights < 7) || ($res_a['access'] === 2 && $user->rights < 7 && (! isset($_SESSION['ap']) || $_SESSION['ap'] !== $res_a['password']) && $owner['id'] !== $user->id)) {
        // Если доступ закрыт
        echo $view->render(
            'system::pages/result',
            [
                'title'    => $title,
                'type'     => 'alert-danger',
                'message'  => __('Access forbidden'),
                'back_url' => './list?user=' . $owner['id'],
            ]
        );
        exit;
    }

    // Параметры комментариев
    $arg = [
        'comments_table' => 'cms_album_comments', // Таблица с комментариями
        'object_table'   => 'cms_album_files',    // Таблица комментируемых объектов
        'script'         => './comments?',      // Имя скрипта (с параметрами вызова)
        'sub_id_name'    => 'img',                // Имя идентификатора комментируемого объекта
        'sub_id'         => $img,                 // Идентификатор комментируемого объекта
        'owner'          => $owner['id'],         // Владелец объекта
        'owner_delete'   => true,                 // Возможность владельцу удалять комментарий
        'owner_reply'    => true,                 // Возможность владельцу отвечать на комментарий
        'owner_edit'     => false,                // Возможность владельцу редактировать комментарий
        'title'          => __('Comments'),       // Название раздела
        'context_top'    => '',         // Выводится вверху списка
        'context_bottom' => '',                   // Выводится внизу списка
        'back_url'       => './show?al=' . $res_obj['album_id'] . '&user=' . $owner['id'],                   // Выводится внизу списка
    ];

    // Ставим метку прочтения
    if ($user->id === $owner['id'] && $res_obj['unread_comments']) {
        $db->exec("UPDATE `cms_album_files` SET `unread_comments` = '0' WHERE `id` = '${img}' LIMIT 1");
    }

    // Показываем комментарии
    $comm = new Johncms\Comments($arg);

    // Обрабатываем метки непрочитанных комментариев
    if ($comm->added && $user->id !== $owner['id']) {
        $db->exec("UPDATE `cms_album_files` SET `unread_comments` = '1' WHERE `id` = '${img}' LIMIT 1");
    }
} else {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => __('Wrong data'),
        ]
    );
}
