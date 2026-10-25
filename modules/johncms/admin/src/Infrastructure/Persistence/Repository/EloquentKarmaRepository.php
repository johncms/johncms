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
        // A delete rather than a truncate: MySQL commits the transaction around a truncate, so the
        // clearing could not be undone together with the rest of what the request did.
        Karma::query()->delete();
        User::query()->update(['karma_plus' => 0, 'karma_minus' => 0]);
    }
}
