<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Domain\Repository\UserCardRepositoryInterface;
use Johncms\Users\Ban;
use Johncms\Users\Repository\BanRepositoryInterface;
use Johncms\Users\User;

/**
 * The bans of an account, the latest first — those in force and the history.
 */
final readonly class GetUserBansUseCase
{
    public function __construct(
        private UserCardRepositoryInterface $users,
        private BanRepositoryInterface $bans,
    ) {
    }

    public function user(int $id): User
    {
        return $this->users->findById($id) ?? throw new UserNotFoundException();
    }

    public function count(User $user): int
    {
        return $this->bans->countForUser($user->id);
    }

    /**
     * @return Collection<int, Ban>
     */
    public function page(User $user, int $limit, int $offset): Collection
    {
        return $this->bans->getForUser($user->id, $limit, $offset);
    }
}
