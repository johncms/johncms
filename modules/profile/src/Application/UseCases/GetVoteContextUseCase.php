<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\VoteContextDTO;
use Johncms\Modules\Profile\Application\Exceptions\KarmaVoteException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class GetVoteContextUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private KarmaRepositoryInterface $karmaRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $targetId): VoteContextDTO
    {
        $target = $this->profileUserRepository->findById($targetId);

        if ($target === null || (! $target->preg && $this->currentUser->rights < 7)) {
            throw new ProfileNotFoundException();
        }

        // Users who disabled karma for themselves or are banned cannot vote at all
        if ($this->currentUser->karma_off || ! empty($this->currentUser->ban)) {
            throw new KarmaVoteException([__('You are not allowed to vote for users')]);
        }

        $config = config('johncms')['karma'];
        $errors = [];

        if ($target->rights && $config['adm']) {
            $errors[] = __('It is forbidden to vote for administration');
        }

        if ($target->ip === $this->currentUser->ip) {
            $errors[] = __('Cheating karma is forbidden');
        }

        if ($this->currentUser->datereg > (time() - 604800) || $this->currentUser->postforum < $config['forum']) {
            $errors[] = sprintf(
                __('Users can take part in voting if they have stayed on a site not less %s and their score on the forum %d posts.'),
                '7 ' . __('days'),
                $config['forum']
            );
        }

        $count = $this->karmaRepository->countVotesGivenTo($this->currentUser->id, $target->id, 0, time() - 86400);
        if ($count) {
            $errors[] = __('You can vote for single user just one time for 24 hours');
        }

        $sum = $this->karmaRepository->sumPointsGivenSince($this->currentUser->id, $this->currentUser->karma_time);
        if (($config['karma_points'] - $sum) <= 0) {
            $errors[] = sprintf(
                __('You have exceeded the limit of votes. New voices will be added %s'),
                date('d.m.y в H:i:s', ($this->currentUser->karma_time + 86400))
            );
        }

        if ($errors) {
            throw new KarmaVoteException($errors);
        }

        return new VoteContextDTO(
            targetId: $target->id,
            targetName: $target->name,
            availablePoints: $config['karma_points'] - $sum,
        );
    }
}
