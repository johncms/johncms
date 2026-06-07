<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\IpHistoryDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\IpHistoryRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\System\Legacy\Tools;
use Johncms\Users\IpHistory;
use Johncms\Users\User;

final readonly class GetIpHistoryUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private IpHistoryRepositoryInterface $ipHistoryRepository,
        private Tools $tools,
        private User $currentUser,
    ) {
    }

    public function execute(int $userId, int $perPage): IpHistoryDTO
    {
        $profileUser = $this->profileUserRepository->findById($userId);

        // Hide non-confirmed profiles from regular users (only admins with rights >= 7 may see them)
        if ($profileUser === null || (! $profileUser->preg && $this->currentUser->rights < 7)) {
            throw new ProfileNotFoundException();
        }

        // IP history is visible to admins and to the profile owner only
        if (! $this->currentUser->rights && $this->currentUser->id !== $profileUser->id) {
            throw new ProfileAccessForbiddenException();
        }

        $paginator = $this->ipHistoryRepository->paginateByUser($profileUser->id, $perPage);

        $items = [];
        foreach ($paginator->items() as $record) {
            if (! $record instanceof IpHistory) {
                continue;
            }
            $ip = long2ip($record->ip);
            $items[] = [
                'ip'           => $ip,
                'search_url'   => '/admin/ip-search/history?ip=' . $ip,
                'display_date' => $this->tools->displayDate($record->time),
            ];
        }

        return new IpHistoryDTO(
            items: $items,
            total: $paginator->total(),
            pagination: $paginator->render(),
            backUrl: '/profile/' . $profileUser->id,
            profileName: $profileUser->name,
        );
    }
}
