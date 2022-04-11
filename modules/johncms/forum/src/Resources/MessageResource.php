<?php

declare(strict_types=1);

namespace Johncms\Forum\Resources;

use Johncms\Forum\Models\ForumMessage;
use Johncms\Http\Resources\AbstractResource;
use Johncms\Users\User;

/**
 * @mixin ForumMessage
 */
class MessageResource extends AbstractResource
{
    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'user'      => $this->getUser(),
            'text'      => $this->post_text,
            'url'       => $this->url,
            'post_time' => $this->post_time,
            'can_edit'  => $this->canEdit(),
            'meta'      => $this->getMeta(),
            'files'     => $this->getFiles(),
        ];
    }

    private function getUser(): array
    {
        return [
            'id'          => $this->user_id,
            'name'        => $this->user_name,
            'status'      => $this->user?->additional_fields?->status,
            'profile_url' => route('personal.profile', ['id' => $this->user_id]),
            'avatar_url'  => $this->user?->avatar_url,
            'is_online'   => $this->user?->is_online,
            'role_names'  => $this->user?->role_names,
        ];
    }

    private function getMeta(): array
    {
        return [
            'edit_count'   => $this->edit_count,
            'edit_time'    => format_date($this->edit_time),
            'editor_name'  => $this->editor_name,
            'deleted'      => $this->deleted,
            'deleted_by'   => $this->deleted_by,
            'restored_by'  => (empty($this->deleted) && ! empty($this->deleted_by)) ? $this->deleted_by : '',
            'ip'           => $this->ip,
            'ip_via_proxy' => $this->ip_via_proxy,
            'user_agent'   => $this->user_agent,
        ];
    }

    private function getFiles(): array
    {
        return [];
    }

    private function canEdit(): bool
    {
        $user = di(User::class);
        if (
            ($user->hasPermission(['forum_manage_posts', 'forum_manage_topics'])/* || $curator*/)
            //|| ($i === 1 && $access === 2 && $message->user_id === $user->id)
            //|| ($message->user_id === $user->id && ! $set_forum['upfp'] && ($start + $i) === $total && $message->date > time() - 300)
            //|| ($message->user_id === $user->id && $set_forum['upfp'] && $start === 0 && $i === 1 && $message->date > time() - 300)
        ) {
            return true;
        }
        return false;
    }
}
