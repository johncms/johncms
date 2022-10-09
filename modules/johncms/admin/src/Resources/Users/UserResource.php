<?php

declare(strict_types=1);

namespace Johncms\Admin\Resources\Users;

use Johncms\Http\Resources\AbstractResource;
use Johncms\Users\User;

/**
 * @property User $model
 * @mixin User
 */
class UserResource extends AbstractResource
{
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'login'      => $this->login,
            'phone'      => $this->phone,
            'createdAt'  => format_date($this->created_at),
            'updatedAt'  => format_date($this->updated_at),
            'profileUrl' => $this->profile_url,
            'avatarUrl'  => $this->avatar_url,
            'editUrl'    => route('admin.editUser', ['id' => $this->id]),
            'deleteUrl'  => '',
        ];
    }
}
