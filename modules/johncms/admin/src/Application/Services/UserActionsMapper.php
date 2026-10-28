<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\RegistrationSettings;
use Johncms\Modules\Admin\Application\Access\UserEditAccess;
use Johncms\Users\Bans\BanAccess;
use Johncms\Users\ProfileLinksInterface;
use Johncms\Users\User;

/**
 * What the visitor may do to an account, as the row of the list and the page of the account
 * offer it — the same actions in both, the destructive ones last.
 *
 * An action is a link, or a form posted with the token when it changes something: a link would be
 * one image tag away from being triggered for an administrator who never asked for it. Every
 * removal opens a dialog saying what would go.
 *
 * The actions are keyed, so a page can leave out what makes no sense on it: the page of the
 * account does not link to itself.
 */
final readonly class UserActionsMapper
{
    public function __construct(
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
        private ProfileLinksInterface $profiles,
        private UserEditAccess $editAccess,
        private BanAccess $banAccess,
        private RegistrationSettings $registration,
    ) {
    }

    /**
     * @param string $returnUrl The page of the list or of the account the actions are taken from.
     * @return array<string, array<string, mixed>>
     */
    public function map(User $user, string $returnUrl): array
    {
        $self = $user->id === $this->currentUser->id();
        // A removed account has no page to come back to, so a removal returns to the list: to the
        // page it was taken from, or to the start of the list when it was the page of the account.
        $backToList = 'back=' . rawurlencode(str_starts_with($returnUrl, UserListControlsMapper::PATH . '?') ? $returnUrl : UserListControlsMapper::PATH);

        $actions = ['card' => $this->link('/admin/users/' . $user->id, __('Profile'), 'user')];

        if ($this->editAccess->mayEdit($user)) {
            $actions['edit'] = $this->link('/admin/users/' . $user->id . '/edit', __('Edit'), 'pencil');
        }

        $siteUrl = $this->profiles->urlOf($user->id);
        if ($siteUrl !== null) {
            $actions['site'] = $this->link($siteUrl, __('Open on the site'), 'external-link');
        }

        if ($this->accessChecker->allows(CorePermissions::ADMIN_ROLES_MANAGE)) {
            $actions['roles'] = $this->link('/admin/users/' . $user->id . '/roles', __('Roles'), 'key-round');
        }

        if ($this->accessChecker->allows(AdminPermissions::AUTH_LOG_VIEW)) {
            $actions['auth_log'] = $this->link('/admin/auth-log?user=' . $user->id, __('Sign-in log'), 'history');
        }

        // Whether this account stands low enough to be banned by the visitor is asked by the form:
        // answering it here would cost a query about the roles of every row of the list.
        if (! $self && $this->banAccess->mayBanAnything()) {
            $actions['ban'] = $this->link('/admin/users/' . $user->id . '/bans/new', __('Ban'), 'ban', false, true);
        }

        if ($this->editAccess->mayResetSettings($user)) {
            $actions['reset_settings'] = $this->link('/admin/users/' . $user->id . '/reset-settings', __('Reset settings'), 'rotate-ccw', false, true);
        }

        // Whether this particular account may be browsed as — nobody may browse as somebody who
        // outranks them — is decided when the button is pressed. One that cannot sign in yet is
        // not offered at all.
        if (! $self && $this->accessChecker->allows(CorePermissions::USERS_IMPERSONATE) && $this->maySignIn($user)) {
            $actions['impersonate'] = $this->post('/impersonation/start/' . $user->id, [], __('Browse as this user'), 'venetian-mask');
        }

        if ($self) {
            return $actions;
        }

        // An account nobody has let in yet goes alone or with the others that came from its
        // address; one that has lived on the site goes with what it leaves behind.
        if (! $user->preg) {
            $actions['delete'] = $this->link('/admin/registrations/' . $user->id . '/delete?' . $backToList, __('Delete'), 'trash-2', true, true);
            $actions['delete_ip'] = $this->link('/admin/registrations/delete-by-ip?ip=' . (int) $user->getRawOriginal('ip') . '&' . $backToList, __('Remove IP'), 'trash-2', true, true);
        } elseif ($this->accessChecker->allows(CorePermissions::ADMIN_SETTINGS_MANAGE)) {
            $actions['delete'] = $this->link('/admin/users/' . $user->id . '/delete?' . $backToList, __('Delete'), 'trash-2', true, true);
        }

        return $actions;
    }

    /**
     * Letting in an account that waits for approval, or null for one let in already.
     *
     * @return array<string, mixed>|null
     */
    public function approve(User $user, string $returnUrl): ?array
    {
        return $user->preg ? null : $this->post('/admin/registrations/approve', ['id' => $user->id, 'back' => $returnUrl], __('Approve'), 'check');
    }

    /**
     * Confirming the e-mail on behalf of an account that has to confirm it before it signs in, or
     * null for any other.
     *
     * @return array<string, mixed>|null
     */
    public function confirmEmail(User $user, string $returnUrl): ?array
    {
        return $this->registration->awaitsEmailConfirmation($user)
            ? $this->post('/admin/registrations/confirm-email', ['id' => $user->id, 'back' => $returnUrl], __('Confirm the e-mail'), 'mail-check')
            : null;
    }

    /**
     * The state of the account as the list and its page show it: waiting for an administrator,
     * waiting for its owner to confirm the e-mail, banned, or none of these.
     */
    public function state(User $user, bool $banned): string
    {
        return match (true) {
            ! $user->preg                                      => 'pending',
            $this->registration->awaitsEmailConfirmation($user) => 'unconfirmed',
            $banned                                            => 'banned',
            default                                            => 'active',
        };
    }

    private function maySignIn(User $user): bool
    {
        return $user->preg && ! $this->registration->awaitsEmailConfirmation($user);
    }

    /**
     * @param bool $dialog The address is a dialog, opened in the modal of the panel.
     * @return array<string, mixed>
     */
    private function link(string $url, string $title, string $icon, bool $danger = false, bool $dialog = false): array
    {
        return ['method' => 'get', 'url' => $url, 'title' => $title, 'icon' => $icon, 'danger' => $danger, 'dialog' => $dialog, 'fields' => []];
    }

    /**
     * @param array<string, int|string> $fields
     * @return array<string, mixed>
     */
    private function post(string $url, array $fields, string $title, string $icon, bool $danger = false): array
    {
        return ['method' => 'post', 'url' => $url, 'title' => $title, 'icon' => $icon, 'danger' => $danger, 'dialog' => false, 'fields' => $fields];
    }
}
