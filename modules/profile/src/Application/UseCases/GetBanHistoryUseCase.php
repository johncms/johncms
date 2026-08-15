<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Profile\Application\Access\BanAccess;
use Johncms\Modules\Profile\Application\DTO\BanHistoryDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\Services\ProfilePermissions;
use Johncms\Modules\Profile\Domain\Repository\BanRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\Ban;
use Johncms\Users\User;
use Johncms\Utils\DurationFormatter;

final readonly class GetBanHistoryUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private BanRepositoryInterface $banRepository,
        private AccessCheckerInterface $accessChecker,
        private BanAccess $banAccess,
    ) {
    }

    public function count(int $targetId): int
    {
        $target = $this->loadTarget($targetId);

        return $this->banRepository->countForUser($target->id);
    }

    public function getPage(int $targetId, int $limit, int $offset): BanHistoryDTO
    {
        $target = $this->loadTarget($targetId);

        $bans = $this->banRepository->getForUser($target->id, $limit, $offset);

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
        $mayLift = $this->accessChecker->allows(ProfilePermissions::BAN_PERMANENT);
        $mayDestroy = $this->accessChecker->allows(ProfilePermissions::BAN_DESTROY);
        // Who applied a ban is of interest to whoever applies them
        $maySeeAuthor = $this->banAccess->mayBanAnything();

        $items = [];
        foreach ($bans as $ban) {
            if (! $ban instanceof Ban) {
                continue;
            }
            $remain = $ban->ban_time - time();
            $period = $ban->ban_time - $ban->ban_while;

            $buttons = [];
            if ($mayLift && $remain > 0) {
                $buttons[] = ['url' => $base . '/' . $ban->id . '/cancel', 'name' => __('Cancel Ban')];
            }
            if ($mayDestroy) {
                $buttons[] = ['url' => $base . '/' . $ban->id . '/delete', 'name' => __('Delete Ban')];
            }

            $items[] = [
                'ban_type_name'    => $types[$ban->ban_type] ?? '',
                'ban_started'      => date('d.m.Y / H:i', $ban->ban_while),
                'reason_formatted' => $ban->ban_reason,
                'time_name'        => $period < 86400000 ? DurationFormatter::format($period) : __('Till cancel'),
                'remain'           => $remain > 0 ? DurationFormatter::format($remain) : '',
                'ban_who'          => $maySeeAuthor ? $ban->ban_who : null,
                'buttons'          => $buttons,
            ];
        }

        return new BanHistoryDTO(
            userName: $target->name,
            items: $items,
            clearHistoryUrl: $mayDestroy ? $base . '/clear' : null,
            backUrl: '/profile/' . $target->id,
        );
    }

    private function loadTarget(int $targetId): User
    {
        $target = $this->profileUserRepository->findById($targetId);

        // An account awaiting confirmation exists only for whoever is allowed to see one
        if ($target === null || (! $target->preg && ! $this->accessChecker->allows(ProfilePermissions::UNCONFIRMED_VIEW))) {
            throw new ProfileNotFoundException();
        }

        return $target;
    }
}
