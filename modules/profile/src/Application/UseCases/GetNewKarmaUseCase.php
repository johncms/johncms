<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Profile\Application\DTO\KarmaListDTO;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Users\Karma;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\PlainTextFormatter;

final readonly class GetNewKarmaUseCase
{
    public function __construct(
        private KarmaRepositoryInterface $karmaRepository,
        private DateFormatterInterface $dateFormatter,
        private SmiliesRendererInterface $smiliesRenderer,
        private CurrentUser $currentUser,
    ) {
    }

    public function count(): int
    {
        return $this->karmaRepository->countVotesReceivedAfter($this->currentUser->id(), time() - 86400);
    }

    public function getPage(int $limit, int $offset): KarmaListDTO
    {
        $userId = $this->currentUser->id();
        $votes = $this->karmaRepository->getReceivedAfter($userId, time() - 86400, $limit, $offset);

        $items = [];
        foreach ($votes as $vote) {
            if (! $vote instanceof Karma) {
                continue;
            }
            $items[] = [
                'type'         => $vote->type,
                'points'       => $vote->points,
                'user_id'      => $vote->user_id,
                'name'         => $vote->name,
                'display_date' => $this->dateFormatter->format($vote->time),
                'text'         => $this->smiliesRenderer->render(PlainTextFormatter::escape($vote->text)),
            ];
        }

        return new KarmaListDTO(
            items: $items,
            filters: [],
            resetUrl: null,
            backUrl: '/profile/' . $userId,
        );
    }
}
