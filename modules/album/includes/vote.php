<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Albums\Photo;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\Http\Request $request
 */

$mod = trim($request->getQuery('mod', '', FILTER_SANITIZE_STRING));
$referer = $request->getHeader('Referer')[0] ?? './';
$ref = filter_var($referer, FILTER_SANITIZE_URL);

// Голосуем за фотографию
if (! $img) {
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

$req = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '${img}' AND `user_id` != " . $user->id);

if ($req->rowCount()) {
    $res = $req->fetch();
    $photo = new Photo($res);

    if ($photo->can_vote) {
        switch ($mod) {
            case 'plus':
                /**
                 * Отдаем положительный голос
                 */
                $db->exec(
                    "INSERT INTO `cms_album_votes` SET
                `user_id` = '" . $user->id . "',
                `file_id` = '${img}',
                `vote` = '1'
            "
                );
                $db->exec("UPDATE `cms_album_files` SET `vote_plus` = '" . ($res['vote_plus'] + 1) . "' WHERE `id` = '${img}'");
                break;

            case 'minus':
                /**
                 * Отдаем отрицательный голос
                 */
                $db->exec(
                    "INSERT INTO `cms_album_votes` SET
                `user_id` = '" . $user->id . "',
                `file_id` = '${img}',
                `vote` = '-1'
            "
                );
                $db->exec("UPDATE `cms_album_files` SET `vote_minus` = '" . ($res['vote_minus'] + 1) . "' WHERE `id` = '${img}'");
                break;
        }
        header('Location: ' . $ref);
    } else {
        echo $view->render(
            'system::pages/result',
            [
                'title'    => $title,
                'type'     => 'alert-danger',
                'message'  => __('You cannot vote for this photo.'),
                'back_url' => htmlspecialchars($ref),
            ]
        );
    }
} else {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => __('Wrong data'),
        ]
    );
}
