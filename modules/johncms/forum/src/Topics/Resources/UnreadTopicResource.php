<?php

declare(strict_types=1);

namespace Johncms\Forum\Topics\Resources;

use Johncms\Forum\Models\ForumTopic;
use Johncms\Http\Resources\AbstractResource;
use Johncms\Users\User;

/**
 * @mixin ForumTopic
 */
class UnreadTopicResource extends AbstractResource
{
    public function toArray(): array
    {
        $user = di(User::class);
        return [
            'name'             => $this->name,
            'user_name'        => $this->user_name,
            'post_count'       => $user?->hasAnyRole() ? $this->mod_post_count : $this->post_count,
            'last_post_author' => $user?->hasAnyRole() ? $this->mod_last_post_author_name : $this->last_post_author_name,
            'last_post_date'   => $user?->hasAnyRole() ? format_date($this->mod_last_post_date) : format_date($this->last_post_date),
            'pinned'           => $this->pinned,
            'has_poll'         => $this->has_poll,
            'deleted'          => $this->deleted,
            'closed'           => $this->closed,
            'has_icons'        => $this->has_icons,
            'url'              => $this->url,
            'last_page_url'    => $this->last_page_url,
            'forum_url'        => $this->last_page_url, // TODO: Change url
            'section_url'      => $this->last_page_url, // TODO: Change url
            'section_name'     => $this->section_name ?? '',
        ];
    }
}
