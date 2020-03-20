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

use Illuminate\Database\Eloquent\Relations\HasMany;

trait UserRelations
{
    /**
     * Баны пользователя
     */
    public function bans(): HasMany
    {
        return $this->hasMany(Ban::class);
    }

    /**
     * История IP пользователя
     */
    public function ipHistory(): HasMany
    {
        return $this->hasMany(IpHistory::class);
    }
}
