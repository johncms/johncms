<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\Role;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Admin\Application\DTO\UserListPageDTO;
use Johncms\Users\User;

/**
 * The rows of the list of users, as admin::users/results draws them: the account, its roles and
 * state, and the actions the visitor may take on it.
 *
 * An action is a link, or a form posted with a token when it changes something: a link would be
 * one image tag away from being triggered for an administrator who never asked for it.
 */
final readonly class UserListRowMapper
{
    /**
     * The permission of the profile module to ban, named by its string: the panel links to the
     * form of that module and does not depend on its classes.
     */
    private const BAN_PERMISSION = 'users.ban.manage';

    public function __construct(
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    /**
     * Whether the rows carry the address the accounts came from — a column of its own.
     */
    public function showsOrigin(): bool
    {
        return $this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function mapPage(UserListPageDTO $page): array
    {
        $rows = [];

        foreach ($page->users as $user) {
            $rows[] = $this->map($user, $page->roles[$user->id] ?? [], $page->banEnds[$user->id] ?? 0);
        }

        return $rows;
    }

    /**
     * @param list<Role> $roles
     * @return array<string, mixed>
     */
    private function map(User $user, array $roles, int $banEnds): array
    {
        $showOrigin = $this->showsOrigin();

        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'profile_url'  => $user->profile_url,
            'online'       => $user->is_online,
            'email'        => (string) $user->mail,
            'email_hidden' => ! $user->mailvis,
            'roles'        => array_map(static fn (Role $role): string => $role->display_name, $roles),
            'last_visit'   => $user->last_visit,
            'posts'        => (int) $user->postforum + (int) $user->postguest,
            'status'       => match (true) {
                ! $user->preg => 'pending',
                $banEnds > 0  => 'banned',
                default       => 'active',
            },
            'ban_ends'     => $banEnds,
            'karma'        => (int) $user->karma_plus - (int) $user->karma_minus,
            'registered'   => (int) $user->datereg,
            // The address of a visitor is for the staff only.
            'ip'           => $showOrigin ? (string) $user->ip : '',
            'ip_url'       => $showOrigin ? '/admin/ip-search?ip=' . $user->ip : '',
            'approve'      => $user->preg ? null : $this->post('/admin/registrations/approve', ['id' => $user->id], __('Approve'), 'check'),
            'actions'      => $this->actions($user),
        ];
    }

    /**
     * What the menu of the row offers, the destructive actions last.
     *
     * @return list<array<string, mixed>>
     */
    private function actions(User $user): array
    {
        $self = $user->id === $this->currentUser->id();

        $actions = [$this->link($user->profile_url, __('Profile'), 'user')];

        if ($this->accessChecker->allows(CorePermissions::ADMIN_ROLES_MANAGE)) {
            $actions[] = $this->link('/admin/users/' . $user->id . '/roles', __('Roles'), 'key-round');
        }

        if ($this->accessChecker->allows(AdminPermissions::AUTH_LOG_VIEW)) {
            $actions[] = $this->link('/admin/auth-log?user=' . $user->id, __('Sign-in log'), 'history');
        }

        if (! $self && $this->accessChecker->allows(self::BAN_PERMISSION)) {
            $actions[] = $this->link('/profile/' . $user->id . '/bans/new', __('Ban'), 'ban');
        }

        // Whether this particular account may be browsed as — nobody may browse as somebody who
        // outranks them — is decided when the button is pressed.
        if (! $self && $this->accessChecker->allows(CorePermissions::USERS_IMPERSONATE)) {
            $actions[] = $this->post('/impersonation/start/' . $user->id, [], __('Browse as this user'), 'venetian-mask');
        }

        if ($self) {
            return $actions;
        }

        // An account nobody has let in yet is removed on the spot, together with the others that
        // came from its address if need be; one that has lived on the site goes through the
        // page that says what will be removed with it.
        if (! $user->preg) {
            $actions[] = $this->post('/admin/registrations/delete', ['id' => $user->id], __('Delete'), 'trash-2', true);
            $actions[] = $this->post('/admin/registrations/delete-by-ip', ['ip' => (int) $user->getRawOriginal('ip')], __('Remove IP'), 'trash-2', true);
        } elseif ($this->accessChecker->allows(CorePermissions::ADMIN_SETTINGS_MANAGE)) {
            $actions[] = $this->link('/admin/users/' . $user->id . '/delete', __('Delete'), 'trash-2', true);
        }

        return $actions;
    }

    /**
     * @return array<string, mixed>
     */
    private function link(string $url, string $title, string $icon, bool $danger = false): array
    {
        return ['method' => 'get', 'url' => $url, 'title' => $title, 'icon' => $icon, 'danger' => $danger, 'fields' => []];
    }

    /**
     * @param array<string, int|string> $fields
     * @return array<string, mixed>
     */
    private function post(string $url, array $fields, string $title, string $icon, bool $danger = false): array
    {
        return ['method' => 'post', 'url' => $url, 'title' => $title, 'icon' => $icon, 'danger' => $danger, 'fields' => $fields];
    }
}
