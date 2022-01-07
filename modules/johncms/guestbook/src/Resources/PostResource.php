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
use Johncms\Users\User;

/**
 * Class PostResource
 *
 * @package Guestbook\Resources
 * @property Guestbook $model
 */
class PostResource extends BaseResource
{
    public function toArray(): array
    {
        return [
            'id'           => $this->model->id,
            'name'         => $this->model->name,
            'is_online'    => $this->model->is_online,
            'created_at'   => $this->model->time,
            'edit_count'   => $this->model->edit_count,
            'edited_by'    => $this->model->edit_who,
            'edited_at'    => $this->model->edit_time,
            'text'         => $this->model->post_text,
            'reply_text'   => $this->model->reply_text,
            'reply_author' => $this->model->admin,
            'replied_at'   => $this->model->otime,
            'user_id'      => $this->model->user_id,
            'user'         => $this->getUser(),
            'meta'         => $this->getMeta(),
        ];
    }

    protected function getUser(): array
    {
        $user_model = $this->model->user;
        if ($user_model !== null) {
            $user = [
                'id'          => $user_model->id,
                'profile_url' => $user_model->profile_url,
                'rights_name' => $user_model->getRoleNames(),
                'status'      => $user_model->additional_fields->status,
            ];
        }

        return $user ?? [];
    }

    protected function getMeta(): array
    {
        $currentUser = di(User::class);
        if ($currentUser?->hasAnyRole()) {
            $meta = [
                'ip'            => $this->model->ip,
                'search_ip_url' => '/admin/search_ip/?ip=' . $this->model->ip,
                'user_agent'    => $this->model->browser,
            ];

            if ($currentUser?->hasPermission('guestbook_delete_posts')) {
                $meta['can_manage'] = true;
                $meta['edit_url'] = '/guestbook/edit?id=' . $this->model->id;
                $meta['delete_url'] = '/guestbook/delpost?id=' . $this->model->id;

                if ($currentUser->rights >= 6) {
                    $meta['reply_url'] = '/guestbook/otvet?id=' . $this->model->id;
                }
            }
        }

        return $meta ?? [];
    }
}
