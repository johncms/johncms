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
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Http\Request $request
 * @var Johncms\NavChain $nav_chain
 */

$data = [];

// Создать / изменить альбом
if (($foundUser['id'] === $user->id && empty($user->ban)) || $user->rights >= 7) {
    if ($al) {
        $title = __('Edit Album');
        $req = $db->prepare('SELECT `name`, `description`, `password`, `access` FROM `cms_album_cat` WHERE `id` = ? AND `user_id` = ?');
        $req->execute([$al, $foundUser['id']]);
        if ($req->rowCount()) {
            $res = $req->fetch();
            $name = $res['name'];
            $description = $res['description'];
            $password = $res['password'];
            $access = $res['access'];
        } else {
            echo $view->render(
                'system::pages/result',
                [
                    'title'   => $title,
                    'type'    => 'alert-danger',
                    'message' => __('Wrong data'),
                ]
            );
            exit;
        }
    } else {
        $title = __('Create Album');
        $name = '';
        $description = '';
        $password = '';
        $access = 4;
    }

    $nav_chain->add($title);

    $error = [];

    if ($request->getMethod() === 'POST') {
        // Принимаем данные
        $name = trim($request->getPost('name', ''));
        $description = trim($request->getPost('description', ''));
        $password = trim($request->getPost('password', ''));
        $access = $request->getPost('access', null, FILTER_VALIDATE_INT);

        // Проверяем на ошибки
        $length_name = mb_strlen($name);
        if ($length_name < 2 || $length_name > 150) {
            $error[] = __('Title') . ': ' . __('Invalid length');
        }

        $description = mb_substr($description, 0, 500);

        if ($access === 2 && empty($password)) {
            $error[] = __('You have not entered password');
        } elseif (($access === 2 && mb_strlen($password) < 3) || mb_strlen($password) > 15) {
            $error[] = __('Password') . ': ' . __('Invalid length');
        }

        if ($access < 1 || $access > 4) {
            $error[] = __('Wrong data');
        }

        // Проверяем, есть ли уже альбом с таким же именем?
        $stmt = $db->prepare('SELECT COUNT(*) FROM `cms_album_cat` WHERE `name` = ? AND `user_id` = ?');
        $stmt->execute([$name, $foundUser['id']]);
        if (! $al && $stmt->fetchColumn()) {
            $error[] = __('The album already exists');
        }

        if (! $error) {
            if ($al) {
                // Изменяем данные в базе
                $db->prepare(
                    'UPDATE `cms_album_files` SET `access` = ? WHERE `album_id` = ? AND `user_id` = ?'
                )->execute([$access, $al, $foundUser['id']]);
                $db->prepare(
                    '
                  UPDATE `cms_album_cat` SET
                  `name` = ?,
                  `description` = ?,
                  `password` = ?,
                  `access` = ?
                  WHERE `id` = ? AND `user_id` = ?
                '
                )->execute(
                    [
                        $name,
                        $description,
                        $password,
                        $access,
                        $al,
                        $foundUser['id'],
                    ]
                );
            } else {
                // Вычисляем сортировку
                $req = $db->query("SELECT `sort` FROM `cms_album_cat` WHERE `user_id` = '" . $foundUser['id'] . "' ORDER BY `sort` DESC LIMIT 1");

                if ($sort = $req->fetchColumn()) {
                    ++$sort;
                } else {
                    $sort = 1;
                }

                // Заносим данные в базу
                $db->prepare(
                    '
                  INSERT INTO `cms_album_cat` SET
                  `user_id` = ?,
                  `name` = ?,
                  `description` = ?,
                  `password` = ?,
                  `access` = ?,
                  `sort` = ?
                '
                )->execute(
                    [
                        $foundUser['id'],
                        $name,
                        $description,
                        $password,
                        $access,
                        $sort,
                    ]
                );
            }

            echo $view->render(
                'system::pages/result',
                [
                    'title'    => $title,
                    'type'     => 'alert-success',
                    'message'  => ($al ? __('Album successfully changed') : __('Album successfully created')),
                    'back_url' => './list?user=' . $foundUser['id'],
                ]
            );
            exit;
        }
    }

    if ($error) {
        $data['error_message'] = $error;
    }

    $data['action_url'] = './edit?user=' . $foundUser['id'] . '&amp;al=' . $al;
    $data['back_url'] = './list?user=' . $foundUser['id'];
    $data['form_data'] = [
        'name'        => $tools->checkout($name),
        'description' => $tools->checkout($description),
        'password'    => $tools->checkout($password),
        'access'      => (int) $access,
    ];
    echo $view->render(
        'album::album_form',
        [
            'title'      => $title,
            'page_title' => $title,
            'data'       => $data,
        ]
    );
}
