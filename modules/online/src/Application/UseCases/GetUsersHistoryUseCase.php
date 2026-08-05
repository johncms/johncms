<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\UseCases;

use Johncms\Modules\Forum\Application\Services\ForumVisitorPlaceFormatter;
use Johncms\Modules\Online\Application\DTO\OnlineItemDTO;
use Johncms\Modules\Online\Domain\Repository\OnlineUserRepositoryInterface;
use Johncms\Users\User;
use Johncms\Users\UserPlaceFormatterInterface;
use Twig\Markup;
use Johncms\Utils\DateFormatterInterface;

final readonly class GetUsersHistoryUseCase
{
    public function __construct(
        private OnlineUserRepositoryInterface $repository,
        private DateFormatterInterface $dateFormatter,
        private UserPlaceFormatterInterface $userPlaceFormatter,
    ) {
    }

    public function count(): int
    {
        return $this->repository->countHistory();
    }

    /**
     * @return list<OnlineItemDTO>
     */
    public function getPage(int $limit, int $offset, ?ForumVisitorPlaceFormatter $placeFormatter): array
    {
        return $this->repository->getHistory($limit, $offset)->map(
            fn (User $user): OnlineItemDTO => new OnlineItemDTO(
                id:                  $user->id,
                name:                $user->name,
                isOnline:            $user->is_online,
                profileUrl:          $user->profile_url,
                displayDate:         $this->dateFormatter->format($user->sestime),
                placeName:           $this->placeName((string) $user->place, $placeFormatter),
                ip:                  $user->ip,
                searchIpUrl:         $user->search_ip_url,
                ipViaProxy:          $user->ip_via_proxy,
                searchIpViaProxyUrl: $user->search_ip_via_proxy_url,
                browser:             $user->browser,
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
