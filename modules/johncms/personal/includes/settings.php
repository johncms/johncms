<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Illuminate\Support\Collection;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$title = __('Settings');

// Массив для основных данных, которые попадут в шаблон
$data = [];

$nav_chain->add(__('My Account'), '/profile/?act=office');
$nav_chain->add(__('Settings'), '?act=settings');
// Проверяем права доступа
if ($user_data->id !== $user->id) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => __('Access forbidden'),
        ]
    );
    exit;
}

$data['buttons'] = [
    [
        'url'    => '?act=settings',
        'name'   => __('General setting'),
        'active' => ! $mod,
    ],
    [
        'url'    => '?act=settings&amp;mod=forum',
        'name'   => __('Forum'),
        'active' => $mod === 'forum',
    ],
    [
        'url'    => '?act=settings&amp;mod=mail',
        'name'   => __('Mail'),
        'active' => $mod === 'mail',
    ],
];

// Пользовательские настройки
switch ($mod) {
    case 'mail':
        $title = __('Mail');
        $nav_chain->add($title);
        $set_mail_user = $user->set_mail ?: ['access' => 0];

        if (isset($_POST['submit'])) {
            $set_mail_user['access'] = isset($_POST['access']) && $_POST['access'] >= 0 && $_POST['access'] <= 2 ? abs((int) ($_POST['access'])) : 0;
            $db->prepare('UPDATE `users` SET `set_mail` = ? WHERE `id` = ?')->execute(
                [
                    serialize($set_mail_user),
                    $user->id,
                ]
            );
            $data['success_message'] = __('Settings saved successfully');
        }
        $data['form_action'] = '?act=settings&amp;mod=mail';
        $data['set_mail_user'] = $set_mail_user;

        echo $view->render(
            'profile::mail_settings',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
        break;

    case 'forum':
        // Настройки Форума
        $title = __('Forum');
        $nav_chain->add($title);
        $default_settings = [
            'farea'    => 0,
            'upfp'     => 0,
            'preview'  => 1,
            'postclip' => 1,
        ];
        $set_forum = array_merge($default_settings, $user->set_forum);

        if (isset($_POST['submit'])) {
            $set_forum['farea'] = isset($_POST['farea']);
            $set_forum['upfp'] = isset($_POST['upfp']);
            $set_forum['preview'] = isset($_POST['preview']);
            $set_forum['postclip'] = isset($_POST['postclip']) ? (int) ($_POST['postclip']) : 1;

            if ($set_forum['postclip'] < 0 || $set_forum['postclip'] > 2) {
                $set_forum['postclip'] = 1;
            }

            $db->prepare('UPDATE `users` SET `set_forum` = ? WHERE `id` = ?')->execute(
                [
                    serialize($set_forum),
                    $user->id,
                ]
            );
            $data['success_message'] = __('Settings saved successfully');
        }

        if (isset($_GET['reset']) || empty($set_forum)) {
            $db->prepare('UPDATE `users` SET `set_forum` = ? WHERE `id` = ?')->execute(
                [
                    serialize($default_settings),
                    $user->id,
                ]
            );
            $data['success_message'] = __('Default settings are set');
        }

        $data['form_action'] = '?act=settings&amp;mod=forum';
        $data['set_forum'] = $set_forum;

        echo $view->render(
            'profile::forum_settings',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
        break;

    default:
        $title = __('General setting');
        $nav_chain->add($title);
        if ($request->getMethod() === 'POST') {
            $set_user = (new Collection($user_data->set_user))->toArray();

            // Записываем новые настройки, заданные пользователем
            $set_user['timeshift'] = isset($_POST['timeshift']) ? (int) ($_POST['timeshift']) : 0;
            $set_user['directUrl'] = isset($_POST['directUrl']);
            $set_user['youtube'] = isset($_POST['youtube']);
            $set_user['fieldHeight'] = isset($_POST['fieldHeight']) ? abs((int) ($_POST['fieldHeight'])) : 3;
            $set_user['kmess'] = isset($_POST['kmess']) ? abs((int) ($_POST['kmess'])) : 10;

            if ($set_user['timeshift'] < -12) {
                $set_user['timeshift'] = -12;
            } elseif ($set_user['timeshift'] > 12) {
                $set_user['timeshift'] = 12;
            }

            if ($set_user['kmess'] < 5) {
                $set_user['kmess'] = 5;
            } elseif ($set_user['kmess'] > 99) {
                $set_user['kmess'] = 99;
            }

            if ($set_user['fieldHeight'] < 1) {
                $set_user['fieldHeight'] = 1;
            } elseif ($set_user['fieldHeight'] > 9) {
                $set_user['fieldHeight'] = 9;
            }

            // Устанавливаем язык
            $lng_select = isset($_POST['iso']) ? trim($_POST['iso']) : false;

            if ($lng_select && array_key_exists($lng_select, $config['lng_list'])) {
                $set_user['lng'] = $lng_select;
                $_SESSION['lng'] = $lng_select;
            }

            // Записываем настройки
            $db->prepare('UPDATE `users` SET `set_user` = ? WHERE `id` = ?')->execute([serialize($set_user), $user->id]);
            $_SESSION['set_ok'] = 1;
            header('Location: ?act=settings');
            exit;
        }

        if (isset($_GET['reset'])) {
            // Задаем настройки по-умолчанию
            $db->exec("UPDATE `users` SET `set_user` = '' WHERE `id` = " . $user->id);
            $_SESSION['reset_ok'] = 1;
            header('Location: ?act=settings');
            exit;
        }

        // Форма ввода пользовательских настроек
        if (isset($_SESSION['set_ok'])) {
            $data['success_message'] = __('Settings saved successfully');
            unset($_SESSION['set_ok']);
        }

        if (isset($_SESSION['reset_ok'])) {
            $data['success_message'] = __('Default settings are set');
            unset($_SESSION['reset_ok']);
        }

        $data['form_action'] = '?act=settings';
        $data['system_time'] = date('H:i', time() + ($config['timeshift'] + $user_data->set_user->timeshift) * 3600);

        // Выбор языка
        if (count($config['lng_list']) > 1) {
            $data['user_lng'] = $user_data->set_user->lng ?? $config['lng'];
            $data['lng_list'] = $config['lng_list'];
        }

        echo $view->render(
            'profile::settings',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
}
