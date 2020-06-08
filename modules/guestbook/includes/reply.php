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

// Add "admin response"
if ($user->rights >= 6 && $id) {
    if (
        isset($_POST['submit'], $_POST['token'], $_SESSION['token'])
        && $_POST['token'] == $_SESSION['token']
    ) {
        $reply = isset($_POST['otv']) ? mb_substr(trim($_POST['otv']), 0, 5000) : '';
        $db->exec(
            "UPDATE `guest` SET
                    `admin` = '" . $user->name . "',
                    `otvet` = " . $db->quote($reply) . ",
                    `otime` = '" . time() . "'
                    WHERE `id` = '${id}'
                "
        );
        header('location: ./');
    } else {
        $req = $db->query("SELECT * FROM `guest` WHERE `id` = '${id}'");
        $res = $req->fetch();
        $token = mt_rand(1000, 100000);
        $_SESSION['token'] = $token;
        echo $view->render(
            'guestbook::reply',
            [
                'id'         => $id,
                'token'      => $token,
                'message'    => $res,
                'reply_text' => $tools->checkout($res['otvet'], 0, 0),
                'text'       => $tools->checkout($res['text'], 1, 1),
                'bbcode'     => $bbcode->buttons('form', 'otv'),
            ]
        );
    }
}
