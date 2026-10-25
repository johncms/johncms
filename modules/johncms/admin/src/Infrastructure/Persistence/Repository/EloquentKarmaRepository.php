<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Johncms\Modules\Admin\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Users\Karma;
use Johncms\Users\User;

final class EloquentKarmaRepository implements KarmaRepositoryInterface
{
    public function countVotes(): int
    {
        return Karma::query()->count();
    }

    public function resetAll(): void
    {
        Karma::query()->truncate();
        User::query()->update(['karma_plus' => 0, 'karma_minus' => 0]);
    }
}
