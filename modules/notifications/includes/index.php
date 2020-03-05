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

$data = [];
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

// Новые сообщения на форуме
if (isset($all_counters['forum_new']) && $all_counters['forum_new'] > 0) {
    $notifications[] = [
        'name'    => __('New forum posts'),
        'url'     => '/forum/?act=new',
        'counter' => $all_counters['forum_new'],
        'type'    => 'warning',
    ];
}

// Личные сообщения
if (! empty($all_counters['new_mail'])) {
    $notifications[] = [
        'name'    => __('Mail'),
        'url'     => '/mail/?act=input',
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

$data['notifications'] = $notifications;

$notification = (new Johncms\Notifications\Notification());
$total = $notification->count();
$data['total'] = $total;

// Исправляем запрос на несуществующую страницу
if ($start >= $total) {
    $start = max(0, $total - (($total % $user->config->kmess) === 0 ? $user->config->kmess : ($total % $user->config->kmess)));
}

if ($total) {
    $data['items'] = $notification->offset($start)->limit($user->config->kmess)->get();
}

$data['pagination'] = $tools->displayPagination('?', $start, $total, $user->config->kmess);

if (! empty($_SESSION['message'])) {
    $data['message'] = htmlspecialchars($_SESSION['message']);
    unset($_SESSION['message']);
}

// Выводим шаблон списка уведомлений
echo $view->render('notifications::index', ['data' => $data]);
