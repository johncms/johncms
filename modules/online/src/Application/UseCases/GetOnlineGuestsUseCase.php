<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\UseCases;

use Johncms\Modules\Forum\Application\Services\ForumVisitorPlaceFormatter;
use Johncms\Modules\Online\Application\DTO\OnlineItemDTO;
use Johncms\Modules\Online\Domain\Repository\OnlineGuestRepositoryInterface;
use Johncms\Users\GuestSession;
use Johncms\Users\UserPlaceFormatterInterface;
use Twig\Markup;
use Johncms\Utils\DurationFormatter;

final readonly class GetOnlineGuestsUseCase
{
    public function __construct(
        private OnlineGuestRepositoryInterface $repository,
        private UserPlaceFormatterInterface $userPlaceFormatter,
    ) {
    }

    public function count(): int
    {
        return $this->repository->countOnline();
    }

    /**
     * @return list<OnlineItemDTO>
     */
    public function getPage(int $limit, int $offset, ?ForumVisitorPlaceFormatter $placeFormatter): array
    {
        return $this->repository->getOnline($limit, $offset)->map(
            fn (GuestSession $guest): OnlineItemDTO => new OnlineItemDTO(
                id:                  0,
                name:                __('Guest'),
                isOnline:            $guest->is_online,
                profileUrl:          '',
                displayDate:         $guest->movings . ' - ' . DurationFormatter::format(time() - $guest->sestime),
                placeName:           $this->placeName((string) $guest->place, $placeFormatter),
                ip:                  $guest->ip,
                searchIpUrl:         $guest->search_ip_url,
                ipViaProxy:          $guest->ip_via_proxy,
                searchIpViaProxyUrl: $guest->search_ip_via_proxy_url,
                browser:             $guest->browser,
            )
        )->all();
    }

    private function placeName(string $place, ?ForumVisitorPlaceFormatter $placeFormatter): ?Markup
    {
        return $placeFormatter !== null && str_starts_with($place, '/forum')
            ? $placeFormatter->format($place)
            : $this->userPlaceFormatter->format($place);
    }
}
