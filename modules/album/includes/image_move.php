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
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\Http\Request $request
 * @var Johncms\NavChain $nav_chain
 */

$title = __('Move image');
$nav_chain->add($title);
// Перемещение картинки в другой альбом
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
    $image = $req->fetch();
    if ($request->getMethod() === 'POST') {
        $al = $request->getPost('al', 0, FILTER_SANITIZE_NUMBER_INT);
        $req_a = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '${al}' AND `user_id` = " . $foundUser['id']);

        if ($req_a->rowCount()) {
            $res_a = $req_a->fetch();
            $db->exec(
                "UPDATE `cms_album_files` SET
                    `album_id` = '${al}',
                    `access` = '" . $res_a['access'] . "'
                    WHERE `id` = '${img}'
                "
            );
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-success',
                    'message'       => __('Image successfully moved to the selected album'),
                    'back_url'      => './show?al=' . $al . '&amp;user=' . $foundUser['id'],
                    'back_url_name' => __('Continue'),
                ]
            );
        } else {
            echo $view->render(
                'system::pages/result',
                [
                    'title'    => $title,
                    'type'     => 'alert-danger',
                    'message'  => __('Wrong data'),
                    'back_url' => '/album/',
                ]
            );
        }
    } else {
        $req = $db->query("SELECT * FROM `cms_album_cat` WHERE `user_id` = '" . $foundUser['id'] . "' AND `id` != '" . $image['album_id'] . "' ORDER BY `sort` ASC");

        if ($req->rowCount()) {
            $albums = [];
            while ($res = $req->fetch()) {
                $res['name'] = $tools->checkout($res['name']);
                $albums[] = $res;
            }
            $data['action_url'] = './image_move?img=' . $img . '&amp;user=' . $foundUser['id'];
            $data['back_url'] = './show?al=' . $image['album_id'] . '&amp;user=' . $foundUser['id'];
            $data['albums'] = $albums ?? [];
            echo $view->render(
                'album::move_photo',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        } else {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-info',
                    'message'       => __('You must create at least one additional album in order to move the image'),
                    'back_url'      => './list?user=' . $foundUser['id'],
                    'back_url_name' => __('Continue'),
                ]
            );
        }
    }
}
