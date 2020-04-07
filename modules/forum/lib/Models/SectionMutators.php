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

trait SectionMutators
{
    /**
     * Ссылка на страницу просмотра раздела
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        if (empty($this->parent)) {
            $type = ! empty($this->section_type) ? 'type=topics&amp;' : '';
        } else {
            $type = ! empty($this->section_type) ? 'type=topics&amp;' : 'type=section&amp;';
        }

        return '/forum/?' . $type . 'id=' . $this->id;
    }
}
