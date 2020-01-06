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

$nav_chain->add(_t('Profile') . ': ' . $foundUser->name, '?user=' . $foundUser->id);
$nav_chain->add(_t('Guestbook'));

// Параметры Гостевой
$arg = [
    'comments_table'      => 'cms_users_guestbook', // Таблица Гостевой
    'object_table'        => 'users',               // Таблица комментируемых объектов
    'script'              => '?act=guestbook',      // Имя скрипта (с параметрами вызова)
    'sub_id_name'         => 'user',                // Имя идентификатора комментируемого объекта
    'sub_id'              => $foundUser->id,      // Идентификатор комментируемого объекта
    'owner'               => $foundUser->id,      // Владелец объекта
    'owner_delete'        => true,                  // Возможность владельцу удалять комментарий
    'owner_reply'         => true,                  // Возможность владельцу отвечать на комментарий
    'title'               => _t('Guestbook') . ': ' . $foundUser->name,        // Название раздела
    'templates_namespace' => 'system',
    'back_url'            => '?user=' . $foundUser->id,
];

// Показываем комментарии
new Johncms\Comments($arg);

// Обновляем счетчик непрочитанного
if (! $mod && $foundUser->id === $user->id && $foundUser->comm_count !== $foundUser->comm_old) {
    /** @var PDO $db */
    $db = di(PDO::class);
    $db->query("UPDATE `users` SET `comm_old` = '" . $foundUser->comm_count . "' WHERE `id` = " . $user->id);
}
