<?php

declare(strict_types=1);

namespace Johncms\Forum\Resources;

use Illuminate\Support\Str;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Http\Resources\AbstractResource;

/**
 * @mixin ForumTopic
 * @property null|string $forum_name
 * @property null|int $forum_id
 * @property null|string $section_name
 */
class UnreadTopicResource extends AbstractResource
{
    public function toArray(): array
    {
        return [
            'name'             => $this->name,
            'user_name'        => $this->user_name,
            'post_count'       => $this->show_posts_count,
            'last_post_author' => $this->show_last_author,
            'last_post_date'   => $this->show_last_post_date,
            'pinned'           => $this->pinned,
            'has_poll'         => $this->has_poll,
            'deleted'          => $this->deleted,
            'closed'           => $this->closed,
            'has_icons'        => $this->has_icons,
            'url'              => $this->url,
            'last_page_url'    => $this->last_page_url,
            'forum_url'        => $this->buildSectionUrl($this->forum_id, $this->forum_name),
            'forum_name'       => $this->forum_name,
            'section_url'      => $this->buildSectionUrl($this->section_id, $this->section_name),
            'section_name'     => $this->section_name ?? '',
        ];
    }

    private function buildSectionUrl(int $id, string $name): string
    {
        return route('forum.section', [
            'id'          => $id,
            'sectionName' => Str::slug($name),
        ]);
    }
}
