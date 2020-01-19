<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\NavChain;
use Johncms\System\i18n\Translator;
use Johncms\System\Users\User;
use Johncms\System\View\Render;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\Counters $counters
 * @var Render $view
 * @var User $user
 * @var NavChain $nav_chain
 */

$db = di(PDO::class);
$counters = di('counters');
$view = di(Render::class);
$user = di(User::class);
$nav_chain = di(NavChain::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('notifications', __DIR__ . '/templates/');

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('notifications', __DIR__ . '/locale');

$nav_chain->add(__('Notifications'));
$notifications = [];

$all_counters = $counters->notifications();

// Дополнительные уведомления для администраторов
if ($user->rights >= 7) {
    // Пользователи на регистрации
    if (! empty($all_counters['reg_total'])) {
        $notifications[] = [
            'name'    => __('Users on registration'),
            'url'     => '/admin/reg/',
            'counter' => $all_counters['reg_total'],
            'type'    => 'info',
        ];
    }

    // Статьи на модерации
    if (! empty($all_counters['library_mod'])) {
        $notifications[] = [
            'name'    => __('Articles on moderation'),
            'url'     => '/library/?act=premod',
            'counter' => $all_counters['library_mod'],
            'type'    => 'info',
        ];
    }

    // Загрузки на модерации
    if (! empty($all_counters['downloads_mod'])) {
        $notifications[] = [
            'name'    => __('Downloads on moderation'),
            'url'     => '/downloads/?act=mod_files',
            'counter' => ! empty($all_counters['downloads_mod']),
            'type'    => 'info',
        ];
    }
}

// Сообщение о бане
if (! empty($all_counters['ban'])) {
    $notifications[] = [
        'name'    => __('Ban'),
        'url'     => '/profile/?act=ban',
        'counter' => 0,
        'type'    => 'warning',
    ];
}

// Системные сообщения
$list = [];
if (! empty($all_counters['new_sys_mail'])) {
    $notifications[] = [
        'name'    => __('System messages'),
        'url'     => '/mail/?act=systems',
        'counter' => $all_counters['new_sys_mail'],
        'type'    => 'info',
    ];
}

// Личные сообщения
if (! empty($all_counters['new_mail'])) {
    $notifications[] = [
        'name'    => __('Mail'),
        'url'     => '/mail/?act=new',
        'counter' => $all_counters['new_mail'],
        'type'    => 'info',
    ];
}

// Комментарии в личной гостевой
if (! empty($all_counters['guestbook_comment'])) {
    $notifications[] = [
        'name'    => __('Guestbook'),
        'url'     => '/profile/?act=guestbook&amp;user=' . $user->id,
        'counter' => $all_counters['guestbook_comment'],
        'type'    => 'info',
    ];
}

// Комментарии в альбомах
if (! empty($all_counters['new_album_comm'])) {
    $notifications[] = [
        'name'    => __('Comments'),
        'url'     => '/album/?act=top&amp;mod=my_new_comm',
        'counter' => $all_counters['new_album_comm'],
        'type'    => 'info',
    ];
}

if ($user->comm_count > $user->comm_old) {
    $notifications[] = [
        'name'    => __('Guestbook'),
        'url'     => '/profile/?act=guestbook&amp;user=' . $user->id,
        'counter' => $user->comm_count - $user->comm_old,
        'type'    => 'info',
    ];
}

echo $view->render(
    'notifications::index',
    [
        'notifications' => $notifications,
    ]
);
