<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\CurrentUser;
use Johncms\Users\User;

/**
 * Преобразует модель пользователя в массив, ожидаемый шаблоном admin::user_row.
 */
final readonly class AdminUserRowMapper
{
    public function __construct(
        private CurrentUser $currentUser,
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
            // Browsing as somebody changes what the request may do, so it is a POST with a token
            // rather than a link: a link would be one image tag away from being triggered for an
            // administrator who never asked for it.
            'post_buttons'            => $this->impersonationButtons($user),
        ];

        if ($this->currentUser->isValid() && $this->currentUser->id() !== $user->id) {
            $item['user_profile_link'] = '/profile/' . $user->id;
        }

        return $item;
    }

    /**
     * "Browse as this user", for whoever holds the permission. Whether this particular account may
     * be browsed as — nobody may browse as somebody who outranks them — is decided when the button
     * is pressed: answering it here would cost a query about the roles of every row on the page.
     *
     * @return list<array<string, string>>
     */
    private function impersonationButtons(User $user): array
    {
        if ($user->id === $this->currentUser->id() || ! $this->accessChecker->allows(CorePermissions::USERS_IMPERSONATE)) {
            return [];
        }

        return [
            [
                'url'  => '/impersonation/start/' . $user->id,
                'name' => __('Browse as this user'),
            ],
        ];
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
