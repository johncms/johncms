<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\Http\Request $request
 */

// Удалить альбом
if (($al && $foundUser['id'] === $user->id) || $user->rights >= 6) {
    $post = $request->getParsedBody();

    $req_a = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '${al}' AND `user_id` = '" . $foundUser['id'] . "' LIMIT 1");
    if ($req_a->rowCount()) {
        $res_a = $req_a->fetch();
        $title = __('Delete album:') . ' ' . $tools->checkout($res_a['name']);
        if (
            isset($post['delete_token'], $_SESSION['delete_token']) &&
            $_SESSION['delete_token'] === $post['delete_token'] &&
            $request->getMethod() === 'POST'
        ) {
            $req = $db->query('SELECT * FROM `cms_album_files` WHERE `album_id` = ' . $res_a['id']);

            while ($res = $req->fetch()) {
                // Удаляем файлы фотографий
                @unlink(UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['img_name']);
                @unlink(UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['tmb_name']);
                // Удаляем записи из таблицы голосований
                $db->exec('DELETE FROM `cms_album_votes` WHERE `file_id` = ' . $res['id']);
                // Удаляем комментарии
                $db->exec('DELETE FROM `cms_album_comments` WHERE `sub_id` = ' . $res['id']);
            }

            // Удаляем записи из таблиц
            $db->exec('DELETE FROM `cms_album_files` WHERE `album_id` = ' . $res_a['id']);
            $db->exec('DELETE FROM `cms_album_cat` WHERE `id` = ' . $res_a['id']);

            echo $view->render(
                'system::pages/result',
                [
                    'title'    => $title,
                    'type'     => 'alert-success',
                    'message'  => __('Album deleted'),
                    'back_url' => './list?user=' . $foundUser['id'],
                ]
            );
        } else {
            $delete_token = uniqid('', true);
            $_SESSION['delete_token'] = $delete_token;
            $data['delete_token'] = $delete_token;
            $data['action_url'] = './delete?al=' . $al . '&amp;user=' . $foundUser['id'];
            $data['back_url'] = './list?user=' . $foundUser['id'];
            $data['message'] = __('Are you sure you want to delete this album? If it contains photos, they also will be deleted.');
            echo $view->render(
                'album::image_delete',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        }
    } else {
        http_response_code(403);
        echo $view->render(
            'system::pages/result',
            [
                'title'   => $title,
                'type'    => 'alert-danger',
                'message' => __('Wrong data'),
            ]
        );
    }
}
