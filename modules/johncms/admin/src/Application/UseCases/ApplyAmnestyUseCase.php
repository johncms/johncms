<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\BanAmnestyRepositoryInterface;
use Johncms\Users\Bans\BanManager;

final readonly class ApplyAmnestyUseCase
{
    public function __construct(
        private BanAmnestyRepositoryInterface $repository,
        private BanManager $bans,
    ) {
    }

    /**
     * @param bool $clearDatabase true — delete every ban, the karma each cost coming back;
     *                            false — end the bans in force, except those given until lifted.
     */
    public function execute(bool $clearDatabase): void
    {
        if ($clearDatabase) {
            $this->bans->clearAll();

            return;
        }

        $this->repository->unbanActiveShortTerm('--' . __('Amnesty') . '--');
    }
}
