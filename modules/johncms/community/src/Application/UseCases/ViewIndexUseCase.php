<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\UseCases;

use Johncms\Counters;
use Johncms\Modules\Community\Application\DTO\CommunityIndexResultDTO;
use Johncms\Modules\Community\Domain\Repository\CommunityUserRepositoryInterface;

final readonly class ViewIndexUseCase
{
    public function __construct(
        private CommunityUserRepositoryInterface $communityUserRepository,
        private Counters $counters,
    ) {
    }

    public function execute(): CommunityIndexResultDTO
    {
        $title = __('Community');
        $usersCounters = $this->counters->usersCounters();

        return new CommunityIndexResultDTO(
            $title,
            $title,
            $usersCounters['total'],
            $usersCounters['new'],
            $this->communityUserRepository->countAdministrationUsers(),
            $this->communityUserRepository->countBirthdayUsers((int) date('j'), (int) date('n')),
        );
    }
}
