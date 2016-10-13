<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = _t('Profile') . ' | ' . _t('Guestbook');
$headmod = 'my_guest';
if ($user_id && $user['id'] == $user_id) {
    $datauser['comm_old'] = $datauser['comm_count'];
}
require('../incfiles/head.php');

$context_top = '<div class="phdr"><a href="?user=' . $user['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('Guestbook') . '</div>' .
    '<div class="user"><p>' . functions::display_user($user, ['iphide' => 1,]) . '</p></div>';

// Параметры Гостевой
$arg = [
    'comments_table' => 'cms_users_guestbook', // Таблица Гостевой
    'object_table'   => 'users',                 // Таблица комментируемых объектов
    'script'         => '?act=guestbook',              // Имя скрипта (с параметрами вызова)
    'sub_id_name'    => 'user',                   // Имя идентификатора комментируемого объекта
    'sub_id'         => $user['id'],                   // Идентификатор комментируемого объекта
    'owner'          => $user['id'],                    // Владелец объекта
    'owner_delete'   => true,                    // Возможность владельцу удалять комментарий
    'owner_reply'    => true,                     // Возможность владельцу отвечать на комментарий
    'title'          => _t('Comments'),               // Название раздела
    'context_top'    => $context_top              // Выводится вверху списка
];

// Показываем комментарии
$comm = new comments($arg);

// Обновляем счетчик непрочитанного
if (!$mod && $user['id'] == $user_id && $user['comm_count'] != $user['comm_old']) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);
    $db->query("UPDATE `users` SET `comm_old` = '" . $user['comm_count'] . "' WHERE `id` = '$user_id'");
}
