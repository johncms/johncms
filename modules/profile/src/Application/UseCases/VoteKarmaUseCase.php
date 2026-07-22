<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\VoteContextDTO;
use Johncms\Modules\Profile\Application\DTO\VoteKarmaCommand;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Notifications\Notification;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Users\User;
use Johncms\Utils\PlainTextFormatter;

final readonly class VoteKarmaUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private KarmaRepositoryInterface $karmaRepository,
        private SmiliesRendererInterface $smiliesRenderer,
        private User $currentUser,
    ) {
    }

    public function execute(VoteKarmaCommand $command, VoteContextDTO $context): void
    {
        $type = $command->type ? 1 : 0;
        $text = mb_substr(trim($command->text), 0, 500);
        $points = abs($command->points);
        if (! $points || $points > $context->availablePoints) {
            $points = 1;
        }

        $this->karmaRepository->addVote(
            $this->currentUser->id,
            $this->currentUser->name,
            $context->targetId,
            $points,
            $type,
            time(),
            $text
        );

        $this->profileUserRepository->addKarmaPoints($context->targetId, $type === 1, $points);

        (new Notification())->create(
            [
                'module'     => 'karma',
                'event_type' => 'new_vote',
                'user_id'    => $context->targetId,
                'sender_id'  => $this->currentUser->id,
                'fields'     => [
                    'user_name'   => htmlspecialchars($this->currentUser->name),
                    'karma_url'   => '/profile/' . $context->targetId . '/karma?type=2',
                    'vote_points' => ($type ? '+' : '-') . $points,
                    'message'     => $this->smiliesRenderer->render(PlainTextFormatter::escape($text)),
                ],
            ]
        );
    }
}
