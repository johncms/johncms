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
 */

// Удалить картинку
if (($img && $foundUser['id'] === $user->id) || $user->rights >= 6) {
    $data = [];
    $title = __('Delete image');
    $post = $request->getParsedBody();

    $req = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '${img}' AND `user_id` = '" . $foundUser['id'] . "' LIMIT 1");
    if ($req->rowCount()) {
        $res = $req->fetch();
        $album = $res['album_id'];
        if (
            isset($post['delete_token'], $_SESSION['delete_token']) &&
            $_SESSION['delete_token'] === $post['delete_token'] &&
            $request->getMethod() === 'POST'
        ) {
            // Удаляем файлы картинок
            @unlink(UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['img_name']);
            @unlink(UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['tmb_name']);

            // Удаляем записи из таблиц
            $db->exec("DELETE FROM `cms_album_files` WHERE `id` = '${img}'");
            $db->exec("DELETE FROM `cms_album_votes` WHERE `file_id` = '${img}'");
            $db->exec("DELETE FROM `cms_album_comments` WHERE `sub_id` = '${img}'");

            header('Location: ./show?al=' . $album . '&user=' . $foundUser['id']);
        } else {
            $delete_token = uniqid('', true);
            $_SESSION['delete_token'] = $delete_token;
            $data['delete_token'] = $delete_token;
            $data['action_url'] = './image_delete?img=' . $img . '&amp;user=' . $foundUser['id'];
            $data['back_url'] = './show?al=' . $album . 'user=' . $foundUser['id'];
            $data['message'] = __('Are you sure you want to delete this image?');
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
                'back_url' => '/album/',
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
            'back_url' => '/album/',
        ]
    );
}
