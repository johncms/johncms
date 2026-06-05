<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\DTO\UsersListResultDTO;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Users\User;

final readonly class GetUsersListUseCase
{
    private const MODERATOR_RIGHTS = 6;

    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private AlbumPhotoRepositoryInterface $albumPhotoRepository,
        private User $currentUser,
    ) {
    }

    public function execute(?string $sex, int $page, int $perPage): UsersListResultDTO
    {
        // Moderators see all albums; regular users only see non-private albums and their own.
        $restrictForUser = $this->currentUser->rights >= self::MODERATOR_RIGHTS
            ? null
            : $this->currentUser->id;

        $paginator = $this->albumRepository->paginateOwnersBySex($sex, $restrictForUser, $page, $perPage);

        $userIds = array_map(static fn (User $user): int => $user->id, $paginator->items());
        $photoCounts = $this->albumPhotoRepository->countByUsers($userIds, $restrictForUser);
        foreach ($paginator->items() as $user) {
            $user->count = $photoCounts[$user->id] ?? 0;
        }

        return new UsersListResultDTO($paginator);
    }
}
