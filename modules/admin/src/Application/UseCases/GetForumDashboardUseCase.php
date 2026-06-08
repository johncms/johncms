<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\ForumAdminRepositoryInterface;

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
