<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Auth\CurrentUser;
use Johncms\Users\GuestSession;
use Johncms\Users\User;

final readonly class ForumVisitorRowMapper
{
    public function __construct(
        private StaffTitles $staffTitles,
        private ForumVisitorPlaceFormatter $placeFormatter,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * @param Collection<int, User|GuestSession> $rows
     * @return array<int, array<string, mixed>>
     */
    public function map(Collection $rows, bool $withPlace): array
    {
        $items = [];
        $this->staffTitles->preload(
            $rows->map(static fn (User|GuestSession $row): int => $row instanceof User ? (int) $row->id : 0)
                ->values()
                ->all()
        );

        foreach ($rows as $row) {
            $items[] = $row instanceof User
                ? $this->mapUser($row, $withPlace)
                : $this->mapGuest($row, $withPlace);
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapUser(User $row, bool $withPlace): array
    {
        $item = [
            'id'                      => $row->id,
            'name'                    => $row->name !== '' ? $row->name : __('Guest'),
            'browser'                 => $row->browser,
            'lastdate'                => $row->lastdate,
            'place'                   => $withPlace ? $this->placeFormatter->format($row->place) : '',
            'user_profile_link'       => '',
            'user_rights_name'        => $this->staffTitles->titleFor((int) $row->id),
            'user_is_online'          => $row->is_online,
            'search_ip_url'           => '/admin/ip-search?ip=' . $row->ip,
            'ip'                      => $row->ip,
            'search_ip_via_proxy_url' => $row->ip_via_proxy !== '' ? '/admin/ip-search?ip=' . $row->ip_via_proxy : '',
            'ip_via_proxy'            => $row->ip_via_proxy !== '' ? $row->ip_via_proxy : 0,
        ];

        if ($this->currentUser->isValid() && $this->currentUser->id() !== $row->id) {
            $item['user_profile_link'] = '/profile/' . $row->id;
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapGuest(GuestSession $row, bool $withPlace): array
    {
        return [
            'id'                      => 0,
            'name'                    => __('Guest'),
            'browser'                 => $row->browser,
            'lastdate'                => $row->lastdate,
            'place'                   => $withPlace ? $this->placeFormatter->format($row->place) : '',
            'user_profile_link'       => '',
            'user_rights_name'        => '',
            'user_is_online'          => $row->is_online,
            'search_ip_url'           => '/admin/ip-search?ip=' . $row->ip,
            'ip'                      => $row->ip,
            'search_ip_via_proxy_url' => $row->ip_via_proxy !== '' ? '/admin/ip-search?ip=' . $row->ip_via_proxy : '',
            'ip_via_proxy'            => $row->ip_via_proxy !== '' ? $row->ip_via_proxy : 0,
        ];
    }
}
