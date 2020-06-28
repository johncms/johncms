<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Guestbook\Models\Guestbook;
use Johncms\System\Http\Request;
use Johncms\Users\User;

/** @var User $user */
$user = di(User::class);

/** @var Request $request */
$request = di(Request::class);

// Delete a single post
if ($user->rights >= 6 && $id) {
    if (isset($_GET['yes'])) {
        (new Guestbook())->where('id', $id)->delete();
        header('Location: ./');
    } else {
        echo $view->render('guestbook::confirm_delete', ['id' => $id]);
    }
}
