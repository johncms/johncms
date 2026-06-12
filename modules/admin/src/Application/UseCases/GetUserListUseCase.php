<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\UserListResultDTO;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;
use Johncms\Modules\Admin\Domain\Repository\UserListRepositoryInterface;

final readonly class GetUserListUseCase
{
    public function __construct(
        private UserListRepositoryInterface $userListRepository,
    ) {
    }

    public function count(): int
    {
        return $this->userListRepository->countApproved();
    }

    public function getPage(UserListSort $sort, int $limit, int $offset): UserListResultDTO
    {
        $users = $this->userListRepository->getApproved($sort, $limit, $offset);

        return new UserListResultDTO($users, $sort);
    }
}
