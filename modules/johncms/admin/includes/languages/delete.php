<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Admin\Languages\Languages;
use Johncms\Http\Request;

/** @var \Johncms\Http\Request $request */
$request = di(Request::class);

$lang_code = $request->getQuery('code');

if (! empty($lang_code)) {
    Languages::remove($lang_code);
    Languages::updateList();
}

$_SESSION['message'] = __('The language was successfully deleted');
header('Location: /admin/languages/?action=manage');
