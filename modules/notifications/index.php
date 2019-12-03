<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\UserInterface;
use Johncms\View\Render;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO                      $db
 * @var Johncms\Utility\Counters $counters
 * @var Render                   $view
 * @var UserInterface            $user
 */

$db = di(PDO::class);
$counters = di('counters');
$view = di(Render::class);
$user = di(UserInterface::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('notifications', __DIR__ . '/templates/');

$notifications = [];

$all_counters = $counters->notifications();

// Дополнительные уведомления для администраторов
if ($user->rights >= 7) {
    // Пользователи на регистрации
    if (! empty($all_counters['reg_total'])) {
        $notifications[] = [
            'name'    => _t('Users on registration'),
            'url'     => '/admin/?act=reg',
            'counter' => $all_counters['reg_total'],
            'type'    => 'info',
        ];
    }

    // Статьи на модерации
    if (! empty($all_counters['library_mod'])) {
        $notifications[] = [
            'name'    => _t('Articles on moderation'),
            'url'     => '/library/?act=premod',
            'counter' => $all_counters['library_mod'],
            'type'    => 'info',
        ];
    }

    // Загрузки на модерации
    if (! empty($all_counters['downloads_mod'])) {
        $notifications[] = [
            'name'    => _t('Downloads on moderation'),
            'url'     => 'downloads/?act=mod_files',
            'counter' => ! empty($all_counters['downloads_mod']),
            'type'    => 'info',
        ];
    }
}

// Сообщение о бане
if (! empty($all_counters['ban'])) {
    $notifications[] = [
        'name'    => _t('Ban', 'system'),
        'url'     => '/profile/?act=ban',
        'counter' => 0,
        'type'    => 'warning',
    ];
}

// Системные сообщения
$list = [];
if (! empty($all_counters['new_sys_mail'])) {
    $notifications[] = [
        'name'    => _t('System messages', 'system'),
        'url'     => '/mail/?act=systems',
        'counter' => $all_counters['new_sys_mail'],
        'type'    => 'info',
    ];
}

// Личные сообщения
if (! empty($all_counters['new_mail'])) {
    $notifications[] = [
        'name'    => _t('Mail', 'system'),
        'url'     => '/mail/?act=new',
        'counter' => $all_counters['new_mail'],
        'type'    => 'info',
    ];
}

// Комментарии в личной гостевой
if (! empty($all_counters['guestbook_comment'])) {
    $notifications[] = [
        'name'    => _t('Guestbook', 'system'),
        'url'     => '/profile/?act=guestbook&amp;user=' . $user->id,
        'counter' => $all_counters['guestbook_comment'],
        'type'    => 'info',
    ];
}

// Комментарии в альбомах
if (! empty($all_counters['new_album_comm'])) {
    $notifications[] = [
        'name'    => _t('Comments', 'system'),
        'url'     => '/album/?act=top&amp;mod=my_new_comm',
        'counter' => $all_counters['new_album_comm'],
        'type'    => 'info',
    ];
}
$breadcrumbs = [
    [
        'url'    => '/',
        'name'   => _t('Home', 'system'),
        'active' => false,
    ],
    [
        'url'    => '/notifications/',
        'name'   => _t('Notifications'),
        'active' => true,
    ],
];
echo $view->render('notifications::index', [
    'notifications' => $notifications,
    'breadcrumbs'   => $breadcrumbs,
]);
