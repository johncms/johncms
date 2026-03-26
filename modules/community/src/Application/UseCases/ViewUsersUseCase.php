<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\UseCases;

use Johncms\Modules\Community\Application\DTO\AdministrationUsersResultDTO;
use Johncms\Modules\Community\Domain\Repository\CommunityUserRepositoryInterface;

final readonly class ViewUsersUseCase
{
    public function __construct(
        private CommunityUserRepositoryInterface $communityUserRepository,
    ) {
    }

    public function execute(int $perPage): AdministrationUsersResultDTO
    {
        $users = $this->communityUserRepository->paginateApprovedUsers($perPage);
        $title = __('List of users');

        return new AdministrationUsersResultDTO(
            $users->items(),
            $users->total(),
            $users->render(),
            $title,
            $title,
        );
    }
}
