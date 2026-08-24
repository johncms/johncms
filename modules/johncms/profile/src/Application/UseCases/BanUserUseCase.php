<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Profile\Application\Access\BanAccess;
use Johncms\Modules\Profile\Application\DTO\BanUserCommand;
use Johncms\Modules\Profile\Application\Exceptions\BanException;
use Johncms\Modules\Profile\Domain\Repository\BanRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Profile\Application\Services\ProfilePermissions;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class BanUserUseCase
{
    public function __construct(
        private BanRepositoryInterface $banRepository,
        private KarmaRepositoryInterface $karmaRepository,
        private ProfileUserRepositoryInterface $profileUserRepository,
        private AccessCheckerInterface $accessChecker,
        private BanAccess $banAccess,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(BanUserCommand $command, User $target): void
    {
        $reason = trim($command->reason);
        if ($reason === '' && empty($command->banref)) {
            $reason = __('Reason not specified');
        }

        $term = $command->term;
        $error = null;

        if (empty($term) || empty($command->timeval) || empty($command->time) || $command->timeval < 1) {
            $error = __('There is no required data');
        }

        if (! $this->banAccess->mayApply($term)) {
            $error = __('You have no rights to ban in this section');
        }

        if ($this->banRepository->countActiveByType($target->id, $term) > 0) {
            $error = __('Ban already active');
        }

        $duration = $this->resolveDuration($command->time, $command->timeval);

        if ($error !== null) {
            throw new BanException($error);
        }

        $now = time();
        $this->banRepository->add($target->id, $now + $duration, $now, $term, $this->currentUser->user()->name, $reason);

        $karmaConfig = config('johncms')['karma'];
        if (! empty($karmaConfig['on'])) {
            $points = $karmaConfig['karma_points'] * 2;
            $this->karmaRepository->addVote(0, __('System'), $target->id, $points, 0, $now, __('Ban'));
            $this->profileUserRepository->addKarmaPoints($target->id, false, $points);
        }
    }

    /**
     * Convert the selected unit and amount into a duration in seconds, capped by the unit and by
     * what the visitor is allowed to hand out.
     */
    private function resolveDuration(int $unit, int $timeval): int
    {
        switch ($unit) {
            case 2: // Hours
                $timeval = min($timeval, 24) * 3600;
                break;
            case 3: // Days
                $timeval = min($timeval, 30) * 86400;
                break;
            case 4: // Till cancel (10 years)
                $timeval = 315360000;
                break;
            default: // Minutes
                $timeval = min($timeval, 60) * 60;
        }

        // A ban of one section is worth a day; the general ban of an account is worth a month;
        // longer than that is for whoever may also lift one.
        if (! $this->accessChecker->allows(ProfilePermissions::BAN_MANAGE) && $timeval > 86400) {
            $timeval = 86400;
        }

        if (! $this->accessChecker->allows(ProfilePermissions::BAN_PERMANENT) && $timeval > 2592000) {
            $timeval = 2592000;
        }

        return $timeval;
    }
}
