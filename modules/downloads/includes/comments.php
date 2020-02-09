<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Downloads\Download;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 */

if (! $config['mod_down_comm'] && $user->rights < 7) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Comments'),
            'type'          => 'alert-danger',
            'message'       => __('Comments are disabled'),
            'back_url'      => $url,
            'back_url_name' => __('Downloads'),
        ]
    );
    exit;
}

$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (($res_down['type'] === 3 && $user->rights < 6 && $user->rights !== 4) || ! $req_down->rowCount() || ! is_file($res_down['dir'] . '/' . $res_down['name'])) {
    http_response_code(404);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('File not found'),
            'type'          => 'alert-danger',
            'message'       => __('File not found'),
            'back_url'      => $url,
            'back_url_name' => __('Downloads'),
        ]
    );
    exit;
}

Download::navigation(['dir' => $res_down['dir'], 'refid' => 1, 'count' => 0]);
$nav_chain->add(htmlspecialchars($res_down['rus_name']), '/downloads/?act=view&id=' . $res_down['id']);
$nav_chain->add(__('Comments'), '/downloads/?act=comments&id=' . $res_down['id']);

$title_pages = htmlspecialchars(mb_substr($res_down['rus_name'], 0, 30));
$title = (mb_strlen($res_down['rus_name']) > 30 ? $title_pages . '...' : $title_pages) . ' - ' . __('Comments');

// Параметры комментариев
$arg = [
    // Поле с числом комментариев
    'object_comm_count'   => 'total',
    // Таблица с комментариями
    'comments_table'      => 'download__comments',
    // Таблица комментируемых объектов
    'object_table'        => 'download__files',
    // Имя скрипта (с параметрами вызова)
    'script'              => '?act=comments',
    // Имя идентификатора комментируемого объекта
    'sub_id_name'         => 'id',
    // Идентификатор комментируемого объекта
    'sub_id'              => $id,
    // Владелец объекта
    'owner'               => false,
    // Возможность владельцу удалять комментарий
    'owner_delete'        => false,
    // Возможность владельцу отвечать на комментарий
    'owner_reply'         => false,
    // Возможность владельцу редактировать комментарий
    'owner_edit'          => false,
    // Название раздела
    'title'               => __('Comments'),
    // Namespace для шаблонов. Заменить для кастомных шаблонов
    'templates_namespace' => 'system',
    // Ссылка на страницу назад
    'back_url'            => '/downloads/?act=view&id=' . $res_down['id'],
];

// Показываем комментарии
new Johncms\Comments($arg);
