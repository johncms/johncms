<?php

declare(strict_types=1);

namespace Johncms\Forum\Topics\Resources;

use Johncms\Forum\Models\ForumTopic;
use Johncms\Http\Resources\AbstractResource;

/**
 * @mixin ForumTopic
 */
class TopicResource extends AbstractResource
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
            'unread'           => $this->unread,
        ];
    }
}
