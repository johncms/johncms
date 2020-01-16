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

/**
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$title = __('Avatars');
$nav_chain->add($title, '?act=avatars');
$data = [];

// Каталог пользовательских Аватаров
if ($id && is_dir(ASSETS_PATH . 'avatars/' . $id)) {
    $avatar = isset($_GET['avatar']) ? (int) ($_GET['avatar']) : false;

    if ($user->isValid() && $avatar && is_file(ASSETS_PATH . 'avatars/' . $id . '/' . $avatar . '.png')) {
        if (isset($_POST['submit'])) {
            // Устанавливаем пользовательский Аватар
            if (@copy(ASSETS_PATH . 'avatars/' . $id . '/' . $avatar . '.png', UPLOAD_PATH . 'users/avatar/' . $user->id . '.png')) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-success',
                        'message'       => __('Avatar has been successfully applied'),
                        'back_url'      => '../profile/?act=edit',
                        'back_url_name' => __('Continue'),
                    ]
                );
            } else {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-danger',
                        'message'       => __('An error occurred'),
                        'back_url'      => './',
                        'back_url_name' => __('Back'),
                    ]
                );
            }
        } else {
            $title = __('Set to Profile');
            $data = [
                'form_action'     => '?act=avatars&amp;id=' . $id . '&amp;avatar=' . $avatar,
                'message'         => __('Are you sure you want to set yourself this avatar?'),
                'img'             => '../assets/avatars/' . $id . '/' . $avatar . '.png',
                'back_url'        => '?act=avatars&amp;id=' . $id,
                'submit_btn_name' => __('Save'),
            ];
            echo $view->render(
                'help::confirm',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        }
    } else {
        // Показываем список Аватаров
        $title = htmlentities(file_get_contents(ASSETS_PATH . 'avatars/' . $id . '/name.txt'), ENT_QUOTES, 'utf-8');
        $nav_chain->add($title);

        // Количество аватаров на страницу
        $per_page = 50;

        $array = glob(ASSETS_PATH . 'avatars/' . $id . '/*.png');
        $total = count($array);
        $end = $start + $per_page;

        if ($end > $total) {
            $end = $total;
        }

        if ($total > 0) {
            $items = [];
            for ($i = $start; $i < $end; $i++) {
                $items[] = [
                    'picture' => '../assets/avatars/' . $id . '/' . basename($array[$i]),
                    'set_url' => $user->isValid() ? '?act=avatars&amp;id=' . $id . '&amp;avatar=' . basename($array[$i]) : '',
                ];
            }
        }

        $data['pagination'] = $tools->displayPagination('?act=avatars&amp;id=' . $id . '&amp;', $start, $total, $per_page);
        $data['total'] = $total;
        $data['per_page'] = $per_page;
        $data['items'] = $items ?? [];
        $data['back_url'] = '?act=avatars';

        echo $view->render(
            'help::avatar_list',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
    }
} else {
    // Показываем каталоги с Аватарами
    $dir = glob(ASSETS_PATH . 'avatars/*', GLOB_ONLYDIR);
    $total_dir = count($dir);
    $items = [];
    for ($i = 0; $i < $total_dir; $i++) {
        $count = (int) count(glob($dir[$i] . '/*.png'));
        $items[] = [
            'url'   => '?act=avatars&amp;id=' . basename($dir[$i]),
            'name'  => htmlentities(file_get_contents($dir[$i] . '/name.txt'), ENT_QUOTES, 'utf-8'),
            'count' => $count,
        ];
    }

    $data['items'] = $items ?? [];
    $data['back_url'] = htmlspecialchars($_SESSION['ref']);

    echo $view->render(
        'help::avatars',
        [
            'title'      => $title,
            'page_title' => $title,
            'data'       => $data,
        ]
    );
}
