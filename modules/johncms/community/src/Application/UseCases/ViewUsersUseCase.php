<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\UseCases;

use Johncms\Modules\Community\Domain\Repository\CommunityUserRepositoryInterface;
use Johncms\Users\User;

final readonly class ViewUsersUseCase
{
    public function __construct(
        private CommunityUserRepositoryInterface $communityUserRepository,
    ) {
    }

    public function count(): int
    {
        return $this->communityUserRepository->countApprovedUsers();
    }

    /**
     * @return array<int, User>
     */
    public function getPage(int $limit, int $offset): array
    {
        return $this->communityUserRepository->getApprovedUsers($limit, $offset)->all();
    }
}
