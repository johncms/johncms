<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Carbon\CarbonInterval;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\Role;
use Johncms\Modules\Admin\Application\DTO\UserCardDTO;
use Johncms\System\i18n\Translator;
use Johncms\Users\BanKind;
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
        private Translator $translator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function map(UserCardDTO $card): array
    {
        $user = $card->user;
        $url = '/admin/users/' . $user->id;
        $actions = $this->actions->map($user, $url);
        unset($actions['card']);

        return [
            'id'            => $user->id,
            'name'          => $user->name,
            'url'           => $url,
            'online'        => $user->is_online,
            'state'         => match (true) {
                ! $user->preg           => 'pending',
                $card->activeBans !== [] => 'banned',
                default                 => 'active',
            },
            'roles'         => array_map(static fn (Role $role): string => $role->display_name, $card->roles),
            'custom_status' => (string) $user->status,
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
            'notes'         => $this->accessChecker->allows(CorePermissions::USERS_NOTES_VIEW) ? (string) $user->admin_notes : '',
            'approve'       => $this->actions->approve($user, $url),
            'roles_url'     => $actions['roles']['url'] ?? '',
            'actions'       => array_values(array_diff_key($actions, ['roles' => true])),
        ];
    }

    /**
     * The pages of an account, one tab each.
     *
     * @return list<array<string, mixed>>
     */
    public function tabs(int $id, string $active): array
    {
        $tabs = [
            'view' => [__('Overview'), '/admin/users/' . $id],
        ];

        $result = [];
        foreach ($tabs as $key => [$title, $url]) {
            $result[] = ['title' => $title, 'url' => $url, 'active' => $key === $active];
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
            'full_name' => (string) $user->imname,
            'sex'       => (string) $user->sex,
            'birthday'  => $user->birthday_date,
            'city'      => (string) $user->live,
            'about'     => $user->formatted_about,
            'site'      => $user->website,
            'phone'     => (string) $user->mibile,
            'skype'     => (string) $user->skype,
            'jabber'    => (string) $user->jabber,
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
