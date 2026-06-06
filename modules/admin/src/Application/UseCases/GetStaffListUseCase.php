<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Support\Collection;
use Johncms\Modules\Admin\Application\DTO\StaffGroupDTO;
use Johncms\Modules\Admin\Application\DTO\StaffListDTO;
use Johncms\Modules\Admin\Application\Services\AdminUserRowMapper;
use Johncms\Modules\Admin\Domain\Enums\UserRights;
use Johncms\Modules\Admin\Domain\Repository\StaffRepositoryInterface;
use Johncms\Users\User;

final readonly class GetStaffListUseCase
{
    public function __construct(
        private StaffRepositoryInterface $staffRepository,
        private AdminUserRowMapper $rowMapper,
    ) {
    }

    public function execute(): StaffListDTO
    {
        $staff = $this->staffRepository->getStaff();

        $definitions = [
            [__('Supervisors'), fn (User $user): bool => $user->rights === UserRights::SUPER_ADMIN->value],
            [__('Administrators'), fn (User $user): bool => $user->rights === UserRights::ADMIN->value],
            [__('Super Moderators'), fn (User $user): bool => $user->rights === UserRights::MODERATOR->value],
            [__('Moderators'), fn (User $user): bool => $user->rights >= 1 && $user->rights <= 5],
        ];

        $groups = [];
        $total = 0;

        foreach ($definitions as [$name, $filter]) {
            /** @var Collection<int, User> $bucket */
            $bucket = $staff->filter($filter);
            if ($bucket->isEmpty()) {
                continue;
            }

            $groups[] = new StaffGroupDTO($name, $this->rowMapper->mapMany($bucket->values()));
            $total += $bucket->count();
        }

        return new StaffListDTO($groups, $total);
    }
}
