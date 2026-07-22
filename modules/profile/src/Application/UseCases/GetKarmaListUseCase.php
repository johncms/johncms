<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\KarmaListDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Users\Karma;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\PlainTextFormatter;

final readonly class GetKarmaListUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private KarmaRepositoryInterface $karmaRepository,
        private DateFormatterInterface $dateFormatter,
        private SmiliesRendererInterface $smiliesRenderer,
        private User $currentUser,
    ) {
    }

    public function count(int $targetId, int $type): int
    {
        $target = $this->loadTarget($targetId);

        return $this->karmaRepository->countReceived($target->id, $this->typeFilter($type));
    }

    public function getPage(int $targetId, int $type, int $limit, int $offset): KarmaListDTO
    {
        $target = $this->loadTarget($targetId);

        $votes = $this->karmaRepository->getReceived($target->id, $this->typeFilter($type), $limit, $offset);

        $isSupervisor = $this->currentUser->rights === 9;
        $base = '/profile/' . $target->id . '/karma';

        $items = [];
        foreach ($votes as $vote) {
            if (! $vote instanceof Karma) {
                continue;
            }
            $item = [
                'type'         => $vote->type,
                'points'       => $vote->points,
                'user_id'      => $vote->user_id,
                'name'         => $vote->name,
                'display_date' => $this->dateFormatter->format($vote->time),
                'text'         => $this->smiliesRenderer->render(PlainTextFormatter::escape($vote->text)),
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
            filters: $filters,
            resetUrl: $isSupervisor ? $base . '/clean' : null,
            backUrl: '/profile/' . $target->id,
        );
    }

    private function loadTarget(int $targetId): User
    {
        $target = $this->profileUserRepository->findById($targetId);

        // Hide non-confirmed profiles from regular users (only admins with rights >= 7 may see them)
        if ($target === null || (! $target->preg && $this->currentUser->rights < 7)) {
            throw new ProfileNotFoundException();
        }

        return $target;
    }

    /**
     * type 2 means "all", any other value filters by the exact vote type.
     */
    private function typeFilter(int $type): ?int
    {
        return $type === 2 ? null : $type;
    }
}
