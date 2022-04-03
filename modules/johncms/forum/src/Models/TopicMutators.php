<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Forum\Models;

use Illuminate\Support\Str;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;
use Johncms\Utility\Numbers;
use Johncms\Utility\Pagination;

/**
 * Trait TopicMutators
 *
 * @package Forum\Models
 *
 * @property User $current_user
 * @property Tools $tools
 *
 * @property string $calculated_meta_description
 * @property string $calculated_meta_keywords
 */
trait TopicMutators
{
    /**
     * Topic url
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return route('forum.topic', ['id' => $this->id, 'topicName' => Str::slug($this->name)]);
    }

    /**
     * The number of posts to display
     *
     * @return string
     */
    public function getShowPostsCountAttribute(): string
    {
        if ($this->current_user?->hasAnyRole()) {
            return (string) Numbers::formatNumber($this->mod_post_count);
        }
        return (string) Numbers::formatNumber($this->post_count);
    }

    /**
     * The name of the author to display
     *
     * @return string
     */
    public function getShowLastAuthorAttribute(): string
    {
        if ($this->current_user?->hasAnyRole()) {
            return $this->mod_last_post_author_name;
        }
        return $this->last_post_author_name;
    }

    /**
     * The date of the last post to display
     *
     * @return string
     */
    public function getShowLastPostDateAttribute(): string
    {
        if ($this->current_user?->hasAnyRole()) {
            return format_date($this->mod_last_post_date);
        }
        return format_date($this->last_post_date);
    }

    /**
     * Url to the last page of the topic
     *
     * @return string
     */
    public function getLastPageUrlAttribute(): string
    {
        $total = $this->current_user?->hasAnyRole() ? $this->mod_post_count : $this->post_count;
        $pagination = new Pagination($total);
        $lastPage = $pagination->getTotalPages();
        $query = [];
        if ($lastPage > 1) {
            $query['page'] = $lastPage;
        }

        return route('forum.topic', ['id' => $this->id, 'topicName' => Str::slug($this->name)], $query);
    }

    public function getHasIconsAttribute(): bool
    {
        return ($this->pinned || $this->has_poll || $this->closed || $this->deleted);
    }

    /**
     * The mark of an unread topic
     *
     * @return bool
     */
    public function getUnreadAttribute(): bool
    {
        return $this->read === 0;
    }

    /**
     * Formatted view count of the topic
     *
     * @return string
     */
    public function getFormattedViewCountAttribute(): string
    {
        return (string) Numbers::formatNumber($this->view_count);
    }

    /**
     * Topic meta description
     *
     * @return string
     */
    public function getCalculatedMetaDescriptionAttribute(): string
    {
        if (! empty($this->meta_description)) {
            return $this->meta_description;
        }

        $config = di('config')['forum']['settings'];
        $template = $config['topic_description'] ?? '';
        return str_replace('#name#', $this->name, $template);
    }

    /**
     * Topic meta keywords
     *
     * @return string
     */
    public function getCalculatedMetaKeywordsAttribute(): string
    {
        if (! empty($this->meta_keywords)) {
            return $this->meta_keywords;
        }

        $config = di('config')['forum']['settings'];
        $template = $config['topic_keywords'] ?? '';
        return str_replace('#name#', $this->name, $template);
    }
}
