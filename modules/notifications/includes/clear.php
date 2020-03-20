<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Notifications\Notification;
use Johncms\System\Http\Request;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var Request $request */
$request = di(Request::class);

if ($request->getMethod() === 'POST') {
    (new Notification())->whereRaw('1=1')->delete();
    $_SESSION['message'] = __('Notifications are cleared!');
    header('Location: /notifications/');
    exit;
}
