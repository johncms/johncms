<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Models;

use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\ShortNumberFormatter;

/**
 * Trait TopicMutators
 *
 * @package Forum\Models
 *
 * @property User $current_user
 * @property DateFormatterInterface $dateFormatter
 *
 * @property string $calculated_meta_description
 * @property string $calculated_meta_keywords
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
        return di(ForumTopicPathService::class)->getTopicUrl($this);
    }

    /**
     * Поличество постов для отображения
     *
     * @return string
     */
    public function getShowPostsCountAttribute(): string
    {
        if ($this->current_user->rights >= 7) {
            return ShortNumberFormatter::format($this->mod_post_count);
        }
        return ShortNumberFormatter::format($this->post_count);
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
            return $this->dateFormatter->format($this->mod_last_post_date);
        }
        return $this->dateFormatter->format($this->last_post_date);
    }

    /**
     * Ссылка на последнюю страницу топика
     *
     * @return string
     */
    public function getLastPageUrlAttribute(): string
    {
        if ($this->current_user->rights >= 7) {
            $page = (int) ceil($this->mod_post_count / $this->current_user->set_user->kmess);
        } else {
            $page = (int) ceil($this->post_count / $this->current_user->set_user->kmess);
        }

        if ($page > 1) {
            return di(ForumTopicPathService::class)->getTopicUrl($this, $page);
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

    /**
     * Formatted view count of the topic
     *
     * @return string
     */
    public function getFormattedViewCountAttribute(): string
    {
        return ShortNumberFormatter::format($this->view_count);
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

        $config = config('forum')['settings'];
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

        $config = config('forum')['settings'];
        $template = $config['topic_keywords'] ?? '';
        return str_replace('#name#', $this->name, $template);
    }
}
