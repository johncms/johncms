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

$title = __('Edit image');

// Редактировать картинку
if (($img && $foundUser['id'] === $user->id) || $user->rights >= 6) {
    $req = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '${img}' AND `user_id` = " . $foundUser['id']);

    if (! $req->rowCount()) {
        // Если альбома не существует, завершаем скрипт
        echo $view->render(
            'system::pages/result',
            [
                'title'    => $title,
                'type'     => 'alert-danger',
                'message'  => __('Wrong data'),
                'back_url' => '/album/',
            ]
        );
        exit;
    }

    $res = $req->fetch();
    $album = $res['album_id'];

    if ($request->getMethod() === 'POST') {
        $description = trim($request->getPost('description', ''));
        $description = mb_substr($description, 0, 1500);
        $db->exec('UPDATE `cms_album_files` SET `description` = ' . $db->quote($description) . " WHERE `id` = '${img}'");
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-success',
                'message'       => __('Image successfully changed'),
                'back_url'      => './show?al=' . $album . '&amp;user=' . $foundUser['id'],
                'back_url_name' => __('Continue'),
            ]
        );
    } else {
        $data['action_url'] = './image_edit?img=' . $img . '&amp;user=' . $foundUser['id'];
        $data['back_url'] = './show?al=' . $album . '&amp;user=' . $foundUser['id'];
        $data['image_url'] = '../upload/users/album/' . $foundUser['id'] . '/' . $res['tmb_name'];
        $data['description'] = $tools->checkout($res['description']);
        $data['error_message'] = $error ?? [];
        echo $view->render(
            'album::edit_photo',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
    }
}
