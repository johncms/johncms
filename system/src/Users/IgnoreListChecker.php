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

use Illuminate\Database\Capsule\Manager;

final class IgnoreListChecker implements IgnoreListCheckerInterface
{
    private const TABLE = 'cms_contact';

    public function isBlockedBy(int $ownerId, int $userId): bool
    {
        if ($ownerId <= 0 || $userId <= 0) {
            return false;
        }

        return Manager::connection()
            ->table(self::TABLE)
            ->where('user_id', $ownerId)
            ->where('from_id', $userId)
            ->where('ban', 1)
            ->exists();
    }
}
