<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Carbon\CarbonInterval;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\Role;
use Johncms\Modules\Admin\Application\Access\UserEditAccess;
use Johncms\Modules\Admin\Application\DTO\UserCardDTO;
use Johncms\System\i18n\Translator;
use Johncms\Users\Bans\BanKind;
use Johncms\Users\IpHistory;
use Johncms\Users\User;

/**
 * The page of an account, as admin::user/view draws it. What only some of the staff may see —
 * where the account came from, a hidden address, the notes about it — is left out for the rest.
 */
final readonly class UserCardMapper
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private UserActionsMapper $actions,
        private UserEditAccess $access,
        private Translator $translator,
    ) {
    }

    /**
     * What every page of the account shows over it: the name, what can be done to it.
     *
     * @return array<string, mixed>
     */
    public function head(User $user): array
    {
        $url = '/admin/users/' . $user->id;
        $actions = $this->actions->map($user, $url);
        // The page does not link to itself, and the edit and the roles stand out as buttons.
        $buttons = array_intersect_key($actions, ['edit' => true, 'roles' => true]);
        $menu = array_diff_key($actions, ['card' => true, 'edit' => true, 'roles' => true]);

        return [
            'id'        => $user->id,
            'name'      => $user->name,
            'url'       => $url,
            'approve'   => $this->actions->approve($user, $url),
            'edit_url'  => $buttons['edit']['url'] ?? '',
            'roles_url' => $buttons['roles']['url'] ?? '',
            'actions'   => array_values($menu),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function map(UserCardDTO $card): array
    {
        $user = $card->user;

        return $this->head($user) + [
            'online'        => $user->is_online,
            'state'         => match (true) {
                ! $user->preg           => 'pending',
                $card->activeBans !== [] => 'banned',
                default                 => 'active',
            },
            'roles'         => array_map(static fn (Role $role): string => $role->display_name, $card->roles),
            'custom_status' => $this->text($user->status),
            'photo'         => $user->photo,
            'account'       => $this->account($user),
            'profile'       => $this->profile($user),
            'activity'      => [
                'forum'       => (int) $user->postforum,
                'guestbook'   => (int) $user->postguest,
                'comments'    => (int) $user->komm,
                'karma_plus'  => (int) $user->karma_plus,
                'karma_minus' => (int) $user->karma_minus,
                'karma_off'   => (bool) $user->karma_off,
            ],
            'origin'        => $this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW) ? $this->origin($card) : null,
            'bans'          => [
                'active' => array_map(
                    static fn (int $kind, int $ends): array => ['title' => BanKind::tryFrom($kind)?->title() ?? (string) $kind, 'ends' => $ends],
                    array_keys($card->activeBans),
                    $card->activeBans
                ),
                'count'  => $card->banCount,
            ],
            'notes'         => $this->access->mayReadNotes() ? (string) $user->admin_notes : '',
        ];
    }

    /**
     * A text field of the profile as it is to be shown. The profile of the site stores what it is
     * given escaped already; the panel stores it as typed. Both read back as the text itself here,
     * and the template escapes it once.
     */
    public function text(mixed $value): string
    {
        return html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * The pages of an account, one tab each, those the visitor may open.
     *
     * @return list<array<string, mixed>>
     */
    public function tabs(User $user, string $active): array
    {
        $url = '/admin/users/' . $user->id;
        $tabs = ['view' => [__('Overview'), $url]];

        if ($this->access->mayEdit($user)) {
            $tabs['edit'] = [__('Edit'), $url . '/edit'];
        }

        $tabs['bans'] = [__('Bans'), $url . '/bans'];

        if ($this->access->mayChangePassword($user)) {
            $tabs['password'] = [__('Password'), $url . '/password'];
        }

        $result = [];
        foreach ($tabs as $key => [$title, $tabUrl]) {
            $result[] = ['title' => $title, 'url' => $tabUrl, 'active' => $key === $active];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function account(User $user): array
    {
        $mayReadEmail = $user->mailvis || $this->accessChecker->allows(CorePermissions::USERS_EMAIL_VIEW);

        return [
            'email'        => $mayReadEmail ? (string) $user->mail : '',
            'email_hidden' => ! $user->mailvis,
            'registered'   => (int) $user->datereg,
            'approved_by'  => (string) $user->regadm,
            'last_visit'   => $user->last_visit,
            'place'        => $user->is_online ? $user->display_place : null,
            'time_on_site' => $this->duration((int) $user->total_on_site),
        ];
    }

    /**
     * What the account tells about itself. The about and the site are markup the model has
     * sanitized; the rest is text.
     *
     * @return array<string, mixed>
     */
    private function profile(User $user): array
    {
        return [
            'full_name' => $this->text($user->imname),
            'gender'    => $user->gender?->title() ?? '',
            'birthday'  => $user->birthday_date,
            'city'      => $this->text($user->live),
            'about'     => $user->formatted_about,
            'site'      => $user->website,
            'phone'     => $this->text($user->mibile),
            'skype'     => $this->text($user->skype),
            'jabber'    => $this->text($user->jabber),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function origin(UserCardDTO $card): array
    {
        $user = $card->user;
        $proxy = (string) $user->ip_via_proxy;

        return [
            'ip'            => (string) $user->ip,
            'ip_url'        => '/admin/ip-search?ip=' . $user->ip,
            'proxy'         => $proxy,
            'proxy_url'     => $proxy !== '' ? '/admin/ip-search?ip=' . $proxy : '',
            'browser'       => (string) $user->browser,
            'history'       => array_values($card->recentIps->map(static fn (IpHistory $entry): array => [
                'ip'    => (string) long2ip((int) $entry->ip),
                'proxy' => (int) $entry->ip_via_proxy > 0 ? (string) long2ip((int) $entry->ip_via_proxy) : '',
                'time'  => (int) $entry->time,
            ])->all()),
            'history_count' => $card->ipCount,
        ];
    }

    /**
     * Time spent on the site, in the two largest units: "3 days 4 hours".
     */
    private function duration(int $seconds): string
    {
        if ($seconds < 60) {
            return '';
        }

        return CarbonInterval::seconds($seconds)->cascade()->locale($this->translator->getLocale())->forHumans(['parts' => 2]);
    }
}
