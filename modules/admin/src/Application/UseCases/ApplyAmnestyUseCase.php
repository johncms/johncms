<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\BanAmnestyRepositoryInterface;

final readonly class ApplyAmnestyUseCase
{
    public function __construct(
        private BanAmnestyRepositoryInterface $repository,
    ) {
    }

    /**
     * @param bool $clearDatabase true — полностью очистить историю банов;
     *                            false — снять только активные краткосрочные баны.
     */
    public function execute(bool $clearDatabase): void
    {
        if ($clearDatabase) {
            $this->repository->clearAllBans();

            return;
        }

        $this->repository->unbanActiveShortTerm('--' . __('Amnesty') . '--');
    }
}
