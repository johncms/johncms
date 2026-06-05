<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\BanUserCommand;
use Johncms\Modules\Profile\Application\Exceptions\BanException;
use Johncms\Modules\Profile\Domain\Repository\BanRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class BanUserUseCase
{
    public function __construct(
        private BanRepositoryInterface $banRepository,
        private KarmaRepositoryInterface $karmaRepository,
        private ProfileUserRepositoryInterface $profileUserRepository,
        private User $currentUser,
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

        if (
            ($this->currentUser->rights === 1 && $term !== 14)
            || ($this->currentUser->rights === 2 && $term !== 12)
            || ($this->currentUser->rights === 3 && $term !== 11)
            || ($this->currentUser->rights === 4 && $term !== 16)
            || ($this->currentUser->rights === 5 && $term !== 15)
        ) {
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
        $this->banRepository->add($target->id, $now + $duration, $now, $term, $this->currentUser->name, $reason);

        $karmaConfig = config('johncms')['karma'];
        if (! empty($karmaConfig['on'])) {
            $points = $karmaConfig['karma_points'] * 2;
            $this->karmaRepository->addVote(0, __('System'), $target->id, $points, 0, $now, __('Ban'));
            $this->profileUserRepository->addKarmaPoints($target->id, false, $points);
        }
    }

    /**
     * Convert the selected unit and amount into a duration in seconds, applying per-unit and per-rights caps.
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

        if ($this->currentUser->rights < 6 && $timeval > 86400) {
            $timeval = 86400;
        }

        if ($this->currentUser->rights < 7 && $timeval > 2592000) {
            $timeval = 2592000;
        }

        return $timeval;
    }
}
