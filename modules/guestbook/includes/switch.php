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

// Switching the mode of operation Guest / admin club
if ($user->rights >= 1 || in_array($user->id, $guestAccess)) {
    if (isset($_GET['do']) && $_GET['do'] === 'set') {
        $_SESSION['ga'] = 1;
    } else {
        unset($_SESSION['ga']);
    }
}
header('Location: /guestbook/');
exit;
