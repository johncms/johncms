<?php

declare(strict_types=1);

namespace Johncms\Forum\Resources;

use Johncms\Forum\ForumPermissions;
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
        $currentUser = di(User::class);
        $canEdit = $this->canEdit();

        return [
            'id'          => $this->id,
            'user'        => $this->getUser(),
            'text'        => $this->post_text,
            'url'         => $this->url,
            'post_time'   => $this->post_time,
            'can_edit'    => $canEdit,
            'meta'        => $this->getMeta(),
            'files'       => $this->getFiles(),

            // User actions
            'reply_url'   => ($currentUser && $currentUser->id != $this->user_id) ? '/forum/?act=say&type=reply&amp;id=' . $this->id . '&start=' : null,
            'quote_url'   => ($currentUser && $currentUser->id != $this->user_id) ? '/forum/?act=say&type=reply&amp;id=' . $this->id . '&start=&cyt' : null,

            // Author or moderator actions
            'edit_url'    => $canEdit ? '/forum/?act=editpost&amp;id=' . $this->id : null,
            'delete_url'  => $canEdit ? '/forum/?act=editpost&amp;do=del&amp;id=' . $this->id : null,
            'restore_url' => ($this->deleted && $currentUser?->hasPermission(ForumPermissions::MANAGE_POSTS)) ? '/forum/?act=editpost&amp;do=restore&amp;id=' . $this->id : null,
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
            'edit_count'              => $this->edit_count,
            'edit_time'               => format_date($this->edit_time),
            'editor_name'             => $this->editor_name,
            'deleted'                 => $this->deleted,
            'deleted_by'              => $this->deleted_by,
            'restored_by'             => (empty($this->deleted) && ! empty($this->deleted_by)) ? $this->deleted_by : '',
            'ip'                      => $this->ip,
            'ip_via_proxy'            => $this->ip_via_proxy,
            'search_ip_url'           => '',
            'search_ip_via_proxy_url' => '',
            'user_agent'              => $this->user_agent,
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
