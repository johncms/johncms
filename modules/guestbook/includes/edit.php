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

/** @var User $user */
$user = di(User::class);

/** @var Request $request */
$request = di(Request::class);

// Edit post
if ($user->rights >= 6 && $id) {
    if (
        isset($_POST['submit'], $_POST['token'], $_SESSION['token'])
        && $_POST['token'] == $_SESSION['token']
    ) {
        $res = $db->query("SELECT `edit_count` FROM `guest` WHERE `id`='${id}'")->fetch();
        $edit_count = $res['edit_count'] + 1;
        $msg = isset($_POST['msg']) ? mb_substr(trim($_POST['msg']), 0, 5000) : '';

        $db->prepare(
            '
                  UPDATE `guest` SET
                  `text` = ?,
                  `edit_who` = ?,
                  `edit_time` = ?,
                  `edit_count` = ?
                  WHERE `id` = ?
                '
        )->execute(
            [
                $msg,
                $user->name,
                time(),
                $edit_count,
                $id,
            ]
        );

        header('location: ./');
    } else {
        $token = mt_rand(1000, 100000);
        $_SESSION['token'] = $token;
        $res = $db->query("SELECT * FROM `guest` WHERE `id` = '${id}'")->fetch();
        $text = htmlentities($res['text'], ENT_QUOTES, 'UTF-8');

        echo $view->render(
            'guestbook::edit',
            [
                'id'      => $id,
                'token'   => $token,
                'message' => $res,
                'text'    => $text,
                'bbcode'  => $bbcode->buttons('form', 'msg'),
            ]
        );
    }
}
