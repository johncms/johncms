<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumAdminRepositoryInterface;

final readonly class GetForumDashboardUseCase
{
    public function __construct(
        private ForumAdminRepositoryInterface $repository,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        return $this->repository->dashboardCounters();
    }
}
