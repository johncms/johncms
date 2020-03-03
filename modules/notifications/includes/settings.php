<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\System\Http\Request;
use Johncms\Users\User;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$title = __('Settings');

$nav_chain->add($title, '/notifications/settings/');

/** @var Request $request */
$request = di(Request::class);

$data = [
    'title'       => $title,
    'page_title'  => $title,
    'back_url'    => '/notifications/',
    'form_action' => '/notifications/settings/',
    'message'     => '',
];

$current_user = (new User())->findOrFail($user->id);

if ($request->getMethod() === 'POST') {
    $show_forum_unread = $request->getPost('show_forum_unread', 0, FILTER_VALIDATE_INT);
    $current_user->update(
        [
            'notification_settings' => [
                'show_forum_unread' => $show_forum_unread ? true : false,
            ],
        ]
    );
    $_SESSION['message'] = __('Settings saved!');
    header('Location: /notifications/settings/');
    exit;
}

if (! empty($_SESSION['message'])) {
    $data['message'] = htmlspecialchars($_SESSION['message']);
    unset($_SESSION['message']);
}

// Стандартные настройки
$default_settings = [
    'show_forum_unread' => true,
];

$data['current_settings'] = array_merge($default_settings, ($current_user->notification_settings ?? []));

// Выводим шаблон настроек уведомлений
echo $view->render('notifications::settings', ['data' => $data]);
