<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\KarmaListDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\System\Legacy\Tools;
use Johncms\Users\Karma;
use Johncms\Users\User;

final readonly class GetKarmaListUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private KarmaRepositoryInterface $karmaRepository,
        private Tools $tools,
        private User $currentUser,
    ) {
    }

    public function execute(int $targetId, int $type, int $perPage): KarmaListDTO
    {
        $target = $this->profileUserRepository->findById($targetId);

        // Hide non-confirmed profiles from regular users (only admins with rights >= 7 may see them)
        if ($target === null || (! $target->preg && $this->currentUser->rights < 7)) {
            throw new ProfileNotFoundException();
        }

        // type 2 means "all", any other value filters by the exact vote type
        $typeFilter = $type === 2 ? null : $type;
        $paginator = $this->karmaRepository->paginateReceived($target->id, $typeFilter, $perPage);
        $paginator->appends(['type' => $type]);

        $isSupervisor = $this->currentUser->rights === 9;
        $base = '/profile/' . $target->id . '/karma';

        $items = [];
        foreach ($paginator->items() as $vote) {
            if (! $vote instanceof Karma) {
                continue;
            }
            $item = [
                'type'         => $vote->type,
                'points'       => $vote->points,
                'user_id'      => $vote->user_id,
                'name'         => $vote->name,
                'display_date' => $this->tools->displayDate($vote->time),
                'text'         => $this->tools->smilies($this->tools->checkout($vote->text)),
            ];
            if ($isSupervisor) {
                $item['delete_url'] = $base . '/delete/' . $vote->id . '?type=' . $type;
            }
            $items[] = $item;
        }

        $filters = [
            'all'      => ['name' => __('All'), 'url' => $base . '?type=2', 'active' => $type === 2],
            'positive' => ['name' => __('Positive'), 'url' => $base . '?type=1', 'active' => $type === 1],
            'negative' => ['name' => __('Negative'), 'url' => $base, 'active' => ! $type],
        ];

        return new KarmaListDTO(
            items: $items,
            total: $paginator->total(),
            pagination: $paginator->render(),
            filters: $filters,
            resetUrl: $isSupervisor ? $base . '/clean' : null,
            backUrl: '/profile/' . $target->id,
        );
    }
}
