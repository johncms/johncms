<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Forum\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait SectionRelations
{

    /**
     * Связь с подразделами
     */
    public function subsections(): HasMany
    {
        return $this->hasMany(self::class, 'parent', 'id');
    }

    /**
     * Связь с топиками
     */
    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class, 'section_id', 'id');
    }
}
