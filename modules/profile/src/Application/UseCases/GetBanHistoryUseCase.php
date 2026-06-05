<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\BanHistoryDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\BanRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\System\Legacy\Tools;
use Johncms\Users\Ban;
use Johncms\Users\User;

final readonly class GetBanHistoryUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private BanRepositoryInterface $banRepository,
        private Tools $tools,
        private User $currentUser,
    ) {
    }

    public function execute(int $targetId, int $perPage): BanHistoryDTO
    {
        $target = $this->profileUserRepository->findById($targetId);

        // Hide non-confirmed profiles from regular users (only admins with rights >= 7 may see them)
        if ($target === null || (! $target->preg && $this->currentUser->rights < 7)) {
            throw new ProfileNotFoundException();
        }

        $paginator = $this->banRepository->paginateForUser($target->id, $perPage);

        $types = [
            1  => __('Full block'),
            2  => __('Private messages'),
            3  => __('Private messages'),
            10 => __('Comments'),
            11 => __('Forum'),
            13 => __('Guestbook'),
            15 => __('Library'),
        ];

        $base = '/profile/' . $target->id . '/bans';
        $isSupervisor = $this->currentUser->rights === 9;

        $items = [];
        foreach ($paginator->items() as $ban) {
            if (! $ban instanceof Ban) {
                continue;
            }
            $remain = $ban->ban_time - time();
            $period = $ban->ban_time - $ban->ban_while;

            $buttons = [];
            if ($this->currentUser->rights >= 7 && $remain > 0) {
                $buttons[] = ['url' => $base . '/' . $ban->id . '/cancel', 'name' => __('Cancel Ban')];
            }
            if ($isSupervisor) {
                $buttons[] = ['url' => $base . '/' . $ban->id . '/delete', 'name' => __('Delete Ban')];
            }

            $items[] = [
                'ban_type_name'    => $types[$ban->ban_type] ?? '',
                'ban_started'      => date('d.m.Y / H:i', $ban->ban_while),
                'reason_formatted' => $this->tools->checkout($ban->ban_reason),
                'time_name'        => $period < 86400000 ? $this->tools->timecount($period) : __('Till cancel'),
                'remain'           => $remain > 0 ? $this->tools->timecount($remain) : '',
                'ban_who'          => $ban->ban_who,
                'buttons'          => $buttons,
            ];
        }

        return new BanHistoryDTO(
            userName: $target->name,
            items: $items,
            total: $paginator->total(),
            pagination: $paginator->render(),
            clearHistoryUrl: $isSupervisor ? $base . '/clear' : null,
            backUrl: '/profile/' . $target->id,
        );
    }
}
