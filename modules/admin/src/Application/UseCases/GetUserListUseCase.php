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

    public function execute(UserListSort $sort, int $page, int $perPage): UserListResultDTO
    {
        $users = $this->userListRepository->paginateApproved($sort, $page, $perPage);

        return new UserListResultDTO($users, $sort);
    }
}
