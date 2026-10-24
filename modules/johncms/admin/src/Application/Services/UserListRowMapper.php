<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\Role;
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
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private UserActionsMapper $actions,
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
     * @param string $listUrl The page of the list the rows are on, for the dialogs to return to.
     * @return list<array<string, mixed>>
     */
    public function mapPage(UserListPageDTO $page, string $listUrl): array
    {
        $rows = [];

        foreach ($page->users as $user) {
            $rows[] = $this->map($user, $page->roles[$user->id] ?? [], $page->banEnds[$user->id] ?? 0, $listUrl);
        }

        return $rows;
    }

    /**
     * @param list<Role> $roles
     * @return array<string, mixed>
     */
    private function map(User $user, array $roles, int $banEnds, string $listUrl): array
    {
        $showOrigin = $this->showsOrigin();

        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'url'          => '/admin/users/' . $user->id,
            'online'       => $user->is_online,
            // An address the account keeps hidden is for whoever may read hidden addresses.
            'email'        => $user->mailvis || $this->accessChecker->allows(CorePermissions::USERS_EMAIL_VIEW) ? (string) $user->mail : '',
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
            'approve'      => $this->actions->approve($user, $listUrl),
            'actions'      => array_values($this->actions->map($user, $listUrl)),
        ];
    }
}
