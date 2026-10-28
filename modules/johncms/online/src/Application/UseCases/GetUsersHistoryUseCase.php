<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\UseCases;

use Johncms\Modules\Online\Application\DTO\OnlineItemDTO;
use Johncms\Modules\Online\Domain\Repository\OnlineUserRepositoryInterface;
use Johncms\Users\ProfileLinksInterface;
use Johncms\Users\User;
use Johncms\Users\UserPlaceFormatterInterface;
use Johncms\Utils\DateFormatterInterface;

final readonly class GetUsersHistoryUseCase
{
    public function __construct(
        private OnlineUserRepositoryInterface $repository,
        private DateFormatterInterface $dateFormatter,
        private UserPlaceFormatterInterface $userPlaceFormatter,
        private ProfileLinksInterface $profileLinks,
    ) {
    }

    public function count(): int
    {
        return $this->repository->countHistory();
    }

    /**
     * @return list<OnlineItemDTO>
     */
    public function getPage(int $limit, int $offset): array
    {
        return $this->repository->getHistory($limit, $offset)->map(
            fn (User $user): OnlineItemDTO => new OnlineItemDTO(
                id:                  $user->id,
                name:                $user->name,
                isOnline:            $user->is_online,
                profileUrl:          $this->profileLinks->urlOf($user->id) ?? '',
                displayDate:         $this->dateFormatter->format($user->sestime),
                placeName:           $this->userPlaceFormatter->format((string) $user->place),
                ip:                  $user->ip,
                searchIpUrl:         $user->search_ip_url,
                ipViaProxy:          $user->ip_via_proxy,
                searchIpViaProxyUrl: $user->search_ip_via_proxy_url,
                browser:             $user->browser,
            )
        )->all();
    }
}
