<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Album\Application\Services\AlbumPermissions;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Users\User;

final readonly class GetUsersListUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private AlbumRepositoryInterface $albumRepository,
        private AlbumPhotoRepositoryInterface $albumPhotoRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function count(?string $sex): int
    {
        return $this->albumRepository->countOwners($sex, $this->restrictForUser());
    }

    /**
     * @return array<int, User>
     */
    public function getPage(?string $sex, int $limit, int $offset): array
    {
        $restrictForUser = $this->restrictForUser();

        $users = $this->albumRepository->getOwners($sex, $restrictForUser, $limit, $offset);

        $userIds = $users->map(static fn (User $user): int => $user->id)->all();
        $photoCounts = $this->albumPhotoRepository->countByUsers($userIds, $restrictForUser);
        foreach ($users as $user) {
            $user->count = $photoCounts[$user->id] ?? 0;
        }

        return $users->all();
    }

    /**
     * Moderators see all albums; regular users only see non-private albums and their own.
     */
    private function restrictForUser(): ?int
    {
        return $this->accessChecker->allows(AlbumPermissions::MODERATE)
            ? null
            : $this->currentUser->id();
    }
}
