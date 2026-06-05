<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\KarmaListDTO;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\System\Legacy\Tools;
use Johncms\Users\Karma;
use Johncms\Users\User;

final readonly class GetNewKarmaUseCase
{
    public function __construct(
        private KarmaRepositoryInterface $karmaRepository,
        private Tools $tools,
        private User $currentUser,
    ) {
    }

    public function execute(int $perPage): KarmaListDTO
    {
        $userId = $this->currentUser->id;
        $paginator = $this->karmaRepository->paginateReceivedAfter($userId, time() - 86400, $perPage);

        $items = [];
        foreach ($paginator->items() as $vote) {
            if (! $vote instanceof Karma) {
                continue;
            }
            $items[] = [
                'type'         => $vote->type,
                'points'       => $vote->points,
                'user_id'      => $vote->user_id,
                'name'         => $vote->name,
                'display_date' => $this->tools->displayDate($vote->time),
                'text'         => $this->tools->smilies($this->tools->checkout($vote->text)),
            ];
        }

        return new KarmaListDTO(
            items: $items,
            total: $paginator->total(),
            pagination: $paginator->render(),
            filters: [],
            resetUrl: null,
            backUrl: '/profile/' . $userId,
        );
    }
}
