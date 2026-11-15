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
use Johncms\Utils\ShortNumberFormatter;

/**
 * Trait TopicMutators
 *
 * @package Forum\Models
 *
 * @property string $calculated_meta_description
 * @property string $calculated_meta_keywords
 */
trait TopicMutators
{
    /**
     * The flags of a topic are nullable in the database, and a template that reads a null
     * attribute of a model falls through to a method of the same name. These four keep the
     * attributes boolean, so `topic.deleted` stays an attribute everywhere.
     */
    public function getClosedAttribute(mixed $value): bool
    {
        return (bool) $value;
    }

    public function getDeletedAttribute(mixed $value): bool
    {
        return (bool) $value;
    }

    public function getPinnedAttribute(mixed $value): bool
    {
        return (bool) $value;
    }

    public function getHasPollAttribute(mixed $value): bool
    {
        return (bool) $value;
    }

    public function getDeletedByAttribute(mixed $value): string
    {
        return (string) $value;
    }

    public function getClosedByAttribute(mixed $value): string
    {
        return (string) $value;
    }

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
     * Formatted view count of the topic
     *
     * @return string
     */
    public function getFormattedViewCountAttribute(): string
    {
        return ShortNumberFormatter::format($this->view_count ?? 0);
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
