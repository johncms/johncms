<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

$headmod = 'my_guest';
$textl = _t('Profile') . ' | ' . _t('Guestbook');
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

if ($systemUser->isValid() && $user['id'] == $systemUser->id) {
    $datauser['comm_old'] = $datauser['comm_count'];
}

require('../system/head.php');

$context_top = '<div class="phdr"><a href="?user=' . $user['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('Guestbook') . '</div>' .
    '<div class="user"><p>' . $tools->displayUser($user, ['iphide' => 1,]) . '</p></div>';

// Параметры Гостевой
$arg = [
    'comments_table' => 'cms_users_guestbook', // Таблица Гостевой
    'object_table'   => 'users',               // Таблица комментируемых объектов
    'script'         => '?act=guestbook',      // Имя скрипта (с параметрами вызова)
    'sub_id_name'    => 'user',                // Имя идентификатора комментируемого объекта
    'sub_id'         => $user['id'],           // Идентификатор комментируемого объекта
    'owner'          => $user['id'],           // Владелец объекта
    'owner_delete'   => true,                  // Возможность владельцу удалять комментарий
    'owner_reply'    => true,                  // Возможность владельцу отвечать на комментарий
    'title'          => _t('Comments'),        // Название раздела
    'context_top'    => $context_top           // Выводится вверху списка
];

// Показываем комментарии
$comm = new Johncms\Comments($arg);

// Обновляем счетчик непрочитанного
if (!$mod && $user['id'] == $systemUser->id && $user['comm_count'] != $user['comm_old']) {
    /** @var PDO $db */
    $db = $container->get(PDO::class);
    $db->query("UPDATE `users` SET `comm_old` = '" . $user['comm_count'] . "' WHERE `id` = " . $systemUser->id);
}
