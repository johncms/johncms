<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Guestbook\Resources;

use Johncms\Guestbook\Models\Guestbook;
use Johncms\Http\Resources\AbstractResource;
use Johncms\Users\User;

/**
 * @mixin Guestbook
 */
class PostResource extends AbstractResource
{
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'is_online'    => $this->is_online,
            'created_at'   => $this->time,
            'edit_count'   => $this->edit_count,
            'edited_by'    => $this->edit_who,
            'edited_at'    => $this->edit_time,
            'text'         => $this->post_text,
            'reply_text'   => $this->reply_text,
            'reply_author' => $this->admin,
            'replied_at'   => $this->otime,
            'user_id'      => $this->user_id,
            'user'         => $this->getUser(),
            'meta'         => $this->getMeta(),
        ];
    }

    protected function getUser(): array
    {
        $userModel = $this->user;
        if ($userModel !== null) {
            $user = [
                'id'          => $userModel->id,
                'profile_url' => $userModel->profile_url,
                'avatar_url'  => $userModel->avatar_url,
                'rights_name' => $userModel->hasAnyRole() ? $userModel->getRoleNames() : '',
                'status'      => $userModel->additional_fields->status,
            ];
        }

        return $user ?? [];
    }

    protected function getMeta(): array
    {
        $currentUser = di(User::class);
        if ($currentUser?->hasAnyRole()) {
            $meta = [
                'ip'            => $this->ip,
                'search_ip_url' => '/admin/search_ip/?ip=' . $this->ip,
                'user_agent'    => $this->browser,
            ];

            if ($currentUser?->hasPermission('guestbook_delete_posts')) {
                $meta['can_manage'] = true;
                $meta['edit_url'] = route('guestbook.edit', ['id' => $this->id]);
                $meta['delete_url'] = route('guestbook.delete', ['id' => $this->id]);
                $meta['reply_url'] = route('guestbook.reply', ['id' => $this->id]);
            }
        }

        return $meta ?? [];
    }
}
