<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

interface IgnoreListCheckerInterface
{
    /**
     * Checks whether the owner of the contact list has blocked the given user.
     */
    public function isBlockedBy(int $ownerId, int $userId): bool;
}
