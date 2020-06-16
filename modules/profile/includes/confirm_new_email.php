<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Users\User;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$view->addData(
    [
        'title'      => __('Email confirmation'),
        'page_title' => __('Email confirmation'),
    ]
);

$code = $request->getQuery('code', '');
$id = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
$confirm_user = (new User())->find($id);
$confirmed = false;
if ($confirm_user !== null && ! empty($confirm_user->new_email) && $confirm_user->confirmation_code === $code) {
    $confirm_user->mail = $confirm_user->new_email;
    $confirm_user->new_email = null;
    $confirm_user->confirmation_code = null;
    $confirm_user->save();
    $confirmed = true;
}

echo $view->render(
    'profile::confirm_new_email',
    [
        'confirm_user' => $confirm_user,
        'confirmed'    => $confirmed,
    ]
);
