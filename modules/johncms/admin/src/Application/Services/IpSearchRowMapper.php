<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Admin\Domain\Enums\IpSearchMode;
use Johncms\Users\User;

/**
 * The accounts found by an address, as admin::ip-search draws them: the account, the address it
 * came from and when, its browser, and the same actions the list of users offers.
 */
final readonly class IpSearchRowMapper
{
    public const PATH = '/admin/ip-search';

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private UserActionsMapper $actions,
    ) {
    }

    /**
     * Whether the rows carry the addresses and the browsers — the columns of the origin.
     */
    public function showsOrigin(): bool
    {
        return $this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW);
    }

    /**
     * @param Collection<int, User> $users
     * @param string                $listUrl The page of the results, for the dialogs to return to.
     * @return list<array<string, mixed>>
     */
    public function map(Collection $users, IpSearchMode $mode, string $listUrl): array
    {
        $showOrigin = $this->showsOrigin();

        return array_values($users->map(fn (User $user): array => [
            'id'        => $user->id,
            'name'      => $user->name,
            'url'       => '/admin/users/' . $user->id,
            'online'    => $user->is_online,
            'ip'        => $showOrigin ? (string) $user->ip : '',
            'ip_url'    => $showOrigin ? $this->searchUrl((string) $user->ip, $mode) : '',
            'proxy'     => $showOrigin ? (string) $user->ip_via_proxy : '',
            'proxy_url' => $showOrigin && (string) $user->ip_via_proxy !== '' ? $this->searchUrl((string) $user->ip_via_proxy, $mode) : '',
            'browser'   => $showOrigin ? (string) $user->browser : '',
            // In the history, when the account came from the address; otherwise when it was last seen.
            'time'      => $mode === IpSearchMode::HISTORY ? (int) $user->getAttribute('seen_at') : (int) $user->lastdate,
            'actions'   => array_values($this->actions->map($user, $listUrl)),
        ])->all());
    }

    public function searchUrl(string $search, IpSearchMode $mode): string
    {
        $path = $mode === IpSearchMode::HISTORY ? self::PATH . '/history' : self::PATH;

        return $search === '' ? $path : $path . '?search=' . rawurlencode($search);
    }
}
