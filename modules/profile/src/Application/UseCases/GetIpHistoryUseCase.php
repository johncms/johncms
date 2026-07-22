<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\IpHistoryDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\IpHistoryRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\IpHistory;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;

final readonly class GetIpHistoryUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private IpHistoryRepositoryInterface $ipHistoryRepository,
        private DateFormatterInterface $dateFormatter,
        private User $currentUser,
    ) {
    }

    public function count(int $userId): int
    {
        $profileUser = $this->loadAccessibleProfile($userId);

        return $this->ipHistoryRepository->countByUser($profileUser->id);
    }

    public function getPage(int $userId, int $limit, int $offset): IpHistoryDTO
    {
        $profileUser = $this->loadAccessibleProfile($userId);

        $records = $this->ipHistoryRepository->getByUser($profileUser->id, $limit, $offset);

        $items = [];
        foreach ($records as $record) {
            if (! $record instanceof IpHistory) {
                continue;
            }
            $ip = long2ip($record->ip);
            $items[] = [
                'ip'           => $ip,
                'search_url'   => '/admin/ip-search/history?ip=' . $ip,
                'display_date' => $this->dateFormatter->format($record->time),
            ];
        }

        return new IpHistoryDTO(
            items: $items,
            backUrl: '/profile/' . $profileUser->id,
            profileName: $profileUser->name,
        );
    }

    private function loadAccessibleProfile(int $userId): User
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

        return $profileUser;
    }
}
