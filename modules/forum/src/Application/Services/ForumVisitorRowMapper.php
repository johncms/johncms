<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Users\GuestSession;
use Johncms\Users\User;

final readonly class ForumVisitorRowMapper
{
    public function __construct(
        private ForumVisitorPlaceFormatter $placeFormatter,
        private User $currentUser,
    ) {
    }

    /**
     * @param Collection<int, User|GuestSession> $rows
     * @return array<int, array<string, mixed>>
     */
    public function map(Collection $rows, bool $withPlace): array
    {
        $items = [];

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
            'rights'                  => $row->rights,
            'browser'                 => $row->browser,
            'lastdate'                => $row->lastdate,
            'place'                   => $withPlace ? $this->placeFormatter->format($row->place) : '',
            'user_profile_link'       => '',
            'user_rights_name'        => $this->getUserRightsNames()[$row->rights] ?? '',
            'user_is_online'          => $row->is_online,
            'search_ip_url'           => '/admin/ip-search?ip=' . $row->ip,
            'ip'                      => $row->ip,
            'search_ip_via_proxy_url' => $row->ip_via_proxy !== '' ? '/admin/ip-search?ip=' . $row->ip_via_proxy : '',
            'ip_via_proxy'            => $row->ip_via_proxy !== '' ? $row->ip_via_proxy : 0,
        ];

        if ($this->currentUser->isValid() && $this->currentUser->id !== $row->id) {
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
            'rights'                  => 0,
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

    /**
     * @return array<int, string>
     */
    private function getUserRightsNames(): array
    {
        return [
            3 => __('Forum moderator'),
            4 => __('Download moderator'),
            5 => __('Library moderator'),
            6 => __('Super moderator'),
            7 => __('Administrator'),
            9 => __('Supervisor'),
        ];
    }
}
