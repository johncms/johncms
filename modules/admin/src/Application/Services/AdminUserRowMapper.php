<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Users\User;

/**
 * Преобразует модель пользователя в массив, ожидаемый шаблоном admin::user_row.
 */
final readonly class AdminUserRowMapper
{
    public function __construct(
        private User $currentUser,
        private AccessCheckerInterface $accessChecker,
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
            'show_origin'             => $this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW),
            // Filled in by the mappers that decorate this row; the template reads them always.
            'active'                  => false,
            'buttons'                 => $this->roleButtons($user),
        ];

        if ($this->currentUser->isValid() && $this->currentUser->id !== $user->id) {
            $item['user_profile_link'] = '/profile/' . $user->id;
        }

        return $item;
    }

    /**
     * The way to the roles of the account, for the staff who may hand them out. A mapper that
     * decorates this row replaces the buttons with its own.
     *
     * @return list<array<string, string>>
     */
    private function roleButtons(User $user): array
    {
        if (! $this->accessChecker->allows(CorePermissions::ADMIN_ROLES_MANAGE)) {
            return [];
        }

        return [
            [
                'url'  => '/admin/users/' . $user->id . '/roles',
                'name' => __('Roles'),
            ],
        ];
    }
}
