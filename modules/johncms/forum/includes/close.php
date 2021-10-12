<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Forum\Models\ForumTopic;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Users\User;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var User $user */
$user = di(User::class);

if (($user->rights !== 3 && $user->rights < 6) || ! $id) {
    header('Location: /forum/');
    exit;
}

try {
    $topic = (new ForumTopic())->findOrFail($id);
    $topic->update(['closed' => isset($_GET['closed'])]);
    header('Location: ?type=topic&id=' . $id);
} catch (ModelNotFoundException $exception) {
    pageNotFound();
}
