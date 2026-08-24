<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Profile\Application\DTO\IpHistoryDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\Services\ProfilePermissions;
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
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
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

        // An account awaiting confirmation exists only for whoever is allowed to see one
        if ($profileUser === null || (! $profileUser->preg && ! $this->accessChecker->allows(ProfilePermissions::UNCONFIRMED_VIEW))) {
            throw new ProfileNotFoundException();
        }

        // The addresses an account signed in from are its owner's business and the staff's
        if ($this->currentUser->id() !== $profileUser->id && ! $this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW)) {
            throw new ProfileAccessForbiddenException();
        }

        return $profileUser;
    }
}
