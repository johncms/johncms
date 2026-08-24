<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\DashboardContextDTO;
use Johncms\Modules\Admin\Domain\Repository\DashboardRepositoryInterface;

final readonly class GetDashboardContextUseCase
{
    private const DAY_IN_SECONDS = 86400;

    public function __construct(
        private DashboardRepositoryInterface $dashboardRepository,
    ) {
    }

    public function execute(): DashboardContextDTO
    {
        $dayAgo = time() - self::DAY_IN_SECONDS;

        return new DashboardContextDTO(
            activeUsersToday: $this->dashboardRepository->countActiveUsersSince($dayAgo),
            registeredUsersToday: $this->dashboardRepository->countRegisteredUsersSince($dayAgo),
            forumMessagesToday: $this->dashboardRepository->countForumMessagesSince($dayAgo),
        );
    }
}
