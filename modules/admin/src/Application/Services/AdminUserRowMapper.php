<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Users\User;

/**
 * Преобразует модель пользователя в массив, ожидаемый шаблоном admin::user_row.
 */
final readonly class AdminUserRowMapper
{
    public function __construct(
        private User $currentUser,
    ) {
    }

    /**
     * @param Collection<int, User> $users
     * @return array<int, array<string, mixed>>
     */
    public function mapMany(Collection $users): array
    {
        return $users->map(fn (User $user): array => $this->map($user))->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function map(User $user): array
    {
        $item = [
            'id'                      => $user->id,
            'name'                    => $user->name,
            'rights'                  => $user->rights,
            'browser'                 => $user->browser,
            'user_is_online'          => $user->is_online,
            'user_profile_link'       => '',
            'ip'                      => $user->ip,
            'search_ip_url'           => '/admin/ip-search?ip=' . $user->ip,
            'ip_via_proxy'            => $user->ip_via_proxy !== '' ? $user->ip_via_proxy : '',
            'search_ip_via_proxy_url' => $user->ip_via_proxy !== '' ? '/admin/ip-search?ip=' . $user->ip_via_proxy : '',
            // The address and the user agent of a visitor are for the staff only.
            'show_origin'             => $this->currentUser->rights >= 3,
            // Filled in by the mappers that decorate this row; the template reads them always.
            'active'                  => false,
            'buttons'                 => [],
        ];

        if ($this->currentUser->isValid() && $this->currentUser->id !== $user->id) {
            $item['user_profile_link'] = '/profile/' . $user->id;
        }

        return $item;
    }
}
