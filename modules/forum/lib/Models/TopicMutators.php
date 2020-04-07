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

use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

/**
 * Trait TopicMutators
 *
 * @package Forum\Models
 *
 * @property User $current_user
 * @property Tools $tools
 */
trait TopicMutators
{
    /**
     * Ссылка на страницу просмотра топика
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return '/forum/?type=topic&amp;id=' . $this->id;
    }

    /**
     * Поличество постов для отображения
     *
     * @return string
     */
    public function getShowPostsCountAttribute(): string
    {
        if ($this->current_user->rights >= 7) {
            return (string) $this->tools->formatNumber($this->mod_post_count);
        }
        return (string) $this->tools->formatNumber($this->post_count);
    }

    /**
     * Автор поста для отображения
     *
     * @return string
     */
    public function getShowLastAuthorAttribute(): string
    {
        if ($this->current_user->rights >= 7) {
            return $this->mod_last_post_author_name;
        }
        return $this->last_post_author_name;
    }

    /**
     * Дата последнего поста для отображения
     *
     * @return string
     */
    public function getShowLastPostDateAttribute(): string
    {
        if ($this->current_user->rights >= 7) {
            return $this->tools->displayDate($this->mod_last_post_date);
        }
        return $this->tools->displayDate($this->last_post_date);
    }

    /**
     * Ссылка на последнюю страницу топика
     *
     * @return string
     */
    public function getLastPageUrlAttribute(): string
    {
        if ($this->current_user->rights >= 7) {
            $page = ceil($this->mod_post_count / $this->current_user->set_user->kmess);
        } else {
            $page = ceil($this->post_count / $this->current_user->set_user->kmess);
        }

        if ($page > 1) {
            return '/forum/?type=topic&amp;id=' . $this->id . '&amp;page=' . $page;
        }

        return '';
    }

    /**
     * Ссылка на последнюю страницу топика
     *
     * @return bool
     */
    public function getHasIconsAttribute(): bool
    {
        return ($this->pinned || $this->has_poll || $this->closed || $this->deleted);
    }

    /**
     * Признак непрочитанного топика
     *
     * @return bool
     */
    public function getUnreadAttribute(): bool
    {
        return $this->read !== null && $this->read === 0;
    }
}
