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

$nav_chain->add(__('Profile') . ': ' . $user_data->name, '?user=' . $user_data->id);
$nav_chain->add(__('Guestbook'));

// Параметры Гостевой
$arg = [
    'comments_table'      => 'cms_users_guestbook', // Таблица Гостевой
    'object_table'        => 'users',               // Таблица комментируемых объектов
    'script'              => '?act=guestbook',      // Имя скрипта (с параметрами вызова)
    'sub_id_name'         => 'user',                // Имя идентификатора комментируемого объекта
    'sub_id'              => $user_data->id,      // Идентификатор комментируемого объекта
    'owner'               => $user_data->id,      // Владелец объекта
    'owner_delete'        => true,                  // Возможность владельцу удалять комментарий
    'owner_reply'         => true,                  // Возможность владельцу отвечать на комментарий
    'title'               => __('Guestbook') . ': ' . $user_data->name,        // Название раздела
    'templates_namespace' => 'system',
    'back_url'            => '?user=' . $user_data->id,
];

// Показываем комментарии
new Johncms\Comments($arg);

// Обновляем счетчик непрочитанного
if (! $mod && $user_data->id === $user->id && $user_data->comm_count !== $user_data->comm_old) {
    /** @var PDO $db */
    $db = di(PDO::class);
    $db->query("UPDATE `users` SET `comm_old` = '" . $user_data->comm_count . "' WHERE `id` = " . $user->id);
}
