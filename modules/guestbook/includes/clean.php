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

// Cleaning Guest
if ($user->rights >= 7) {
    if (! empty($_POST)) {
        // We clean the Guest, according to the specified parameters
        $adm = isset($_SESSION['ga']) ? 1 : 0;
        $cl = $request->getPost('cl', 0, FILTER_VALIDATE_INT);

        switch ($cl) {
            case '1':
                // Clean messages older than 1 day
                (new Guestbook())->where('adm', $adm)->where('time', '<', (time() - 86400))->delete();
                $message = __('All messages older than 1 day were deleted');
                break;
            case '2':
                // Perform a full cleanup
                (new Guestbook())->where('adm', $adm)->delete();
                $message = __('Full clearing is finished');
                break;
            default:
                // Clean messages older than 1 week""
                (new Guestbook())->where('adm', $adm)->where('time', '<', (time() - 604800))->delete();
                $message = __('All messages older than 1 week were deleted');
        }

        echo $view->render(
            'guestbook::result',
            [
                'title'    => __('Clear guestbook'),
                'message'  => $message,
                'type'     => 'success',
                'back_url' => '/guestbook/',
            ]
        );
    } else {
        // Request cleaning options
        echo $view->render('guestbook::clear');
    }
} else {
    header('Location: /');
    exit;
}
