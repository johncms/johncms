<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Modules\Forum\Application\Controllers\ForumIndexController;

unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

echo di(ForumIndexController::class)();
